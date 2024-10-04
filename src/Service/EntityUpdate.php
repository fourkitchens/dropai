<?php

namespace Drupal\dropai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\dropai\Event\DropaiEmbeddingEvent;
use Drupal\dropai\Plugin\DropaiEmbeddingManager;
use Drupal\dropai\Plugin\DropaiLoaderManager;
use Drupal\dropai\Plugin\DropaiPreprocessorManager;
use Drupal\dropai\Plugin\DropaiSplitterManager;
use Drupal\dropai\Plugin\DropaiStorageManager;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service for updating the vector database as content is updated.
 */
class EntityUpdate {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The DropAI Embedding plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiEmbeddingManager
   */
  protected $dropaiEmbeddingManager;

  /**
   * The DropAI Loader plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiLoaderManager
   */
  protected $dropaiLoaderManager;

  /**
   * The DropAI Preprocessor plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiPreprocessorManager
   */
  protected $dropaiPreprocessorManager;

  /**
   * The DropAI Splitter plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiSplitterManager
   */
  protected $dropaiSplitterManager;

  /**
   * The DropAI storage plugin manager service.
   *
   * @var \Drupal\dropai\Plugin\DropaiStorageManager
   */
  protected $dropaiStorageManager;

  /**
   * The Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new EntityUpdate object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger Factory.
   * @param \Drupal\dropai\Plugin\DropaiEmbeddingManager $dropai_embedding_manager
   *   The DropAI Embedding plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiLoaderManager $dropai_loader_manager
   *   The DropAI Loader plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiPreprocessorManager $dropai_preprocessor_manager
   *   The DropAI Preprocessor plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiSplitterManager $dropai_splitter_manager
   *   The DropAI Splitter plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiStorageManager $dropaiStorageManager
   *    The DropAI storage plugin manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The Event Dispatcher.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $loggerFactory,
    DropaiEmbeddingManager $dropai_embedding_manager,
    DropaiLoaderManager $dropai_loader_manager,
    DropaiPreprocessorManager $dropai_preprocessor_manager,
    DropaiSplitterManager $dropai_splitter_manager,
    DropaiStorageManager $dropaiStorageManager,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $loggerFactory;
    $this->dropaiEmbeddingManager = $dropai_embedding_manager;
    $this->dropaiLoaderManager = $dropai_loader_manager;
    $this->dropaiPreprocessorManager = $dropai_preprocessor_manager;
    $this->dropaiSplitterManager = $dropai_splitter_manager;
    $this->dropaiStorageManager = $dropaiStorageManager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Create function.
   *
   * @param ContainerInterface $container
   *
   * @return \Drupal\dropai\Service\EntityUpdate
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.dropai_embedding'),
      $container->get('plugin.manager.dropai_loader'),
      $container->get('plugin.manager.dropai_preprocessor'),
      $container->get('plugin.manager.dropai_splitter'),
      $container->get('plugin.manager.dropai_storage'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Get the configured plugin if there is one.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function getCofiguredStoragePlugin() {
    $config = $this->configFactory->get('dropai.indexing_settings');
    $storage_plugin_id = $config->get('storage_plugin');

    // Can't if we don't have one.
    if (empty($storage_plugin_id) || $storage_plugin_id === 'none') {
      throw new \Exception('No storage plugin configured.');
    }

    // Get the storage function class from the plugin id.
    return $this->dropaiStorageManager->createInstance($storage_plugin_id);
  }

  /**
   * Upsert an entity in vector database.
   *
   * Used to insert or update content into the vector database when it has been
   * added or updated in Drupal. This method fires on hook_entity_insert() and
   * hook_entity_update().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity in Drupal.
   * @param string
   *   The action.
   */
  public function upsertEntiy(EntityInterface $entity, string $action) {
    // Default configuration.
    $loader_id = 'entityrender';
    $preprocessor = 'plaintext';
    $splitter_id = 'none';
    $embedding_id = 'openai';
    $embedding_model = 'text-embedding-ada-002';

    // Build the loader.
    $loader = $this->dropaiLoaderManager->createInstance($loader_id);
    $source = $loader->load($entity);
    // Build the preprocessor.
    $plugin = $this->dropaiPreprocessorManager->createInstance($preprocessor);
    $processed = $plugin->preprocess($source);
    // Build the splitter.
    $splitMaxSize = 1000;
    $splitOverlap = $splitMaxSize * .01;
    $splitter = $this->dropaiSplitterManager->createInstance($splitter_id);
    $chunks = $splitter->split($processed, maxSize: $splitMaxSize, overlap: $splitOverlap);
    // Build the embedding.
    $embedding = $this->dropaiEmbeddingManager->createInstance($embedding_id);
    $embeddings = $embedding->getEmbeddings($chunks, $embedding_model);

    // Set the metadata values.
    $metadata = [];
    $metadata['entity_id'] = $entity->id();
    $metadata['entity_type'] = $entity->getEntityTypeId();
    $metadata['entity_bundle'] = $entity->bundle();
    $metadata['updated'] = $entity->getChangedTime();
    // Add more fields if the entity is a Node.
    if ($entity instanceof Node) {
      $metadata['source_url'] = $entity->toUrl()->toString();
      $metadata['status'] = $entity->isPublished();
      $metadata['title'] = $entity->getTitle();
      $metadata['content'] = '';
    }

    $id = $entity->getEntityTypeId() . ':' . $entity->id() . ':' . $entity->bundle();
    try {
      // Render the data.
      $data = [
        'vectors' => [
          'id' => $id,
          'values' => $embeddings[0],
          'metadata' => $metadata,
        ],
        'namespace' => $entity->getEntityTypeId(),
      ];

      // Send the event.
      $event_type = $action === 'insert' ? DropaiEmbeddingEvent::INSERT : DropaiEmbeddingEvent::UPDATE;
      $event = new DropaiEmbeddingEvent($data);
      $this->eventDispatcher->dispatch($event, $event_type);

      $this->loggerFactory->get('dropai')->notice(
        '@action @id in vector database: @vector',
        [
          '@action' => $action,
          '@id' => $id,
          '@vector' => print_r($embeddings[0], 1),
        ]
      );
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('dropai')->error($e->getMessage());
    }
  }

  /**
   * Remove an entity from the vector database.
   *
   * Used to remove content from the vector database when it has been deleted
   * from Drupal. This method fires on hook_entity_delete().
   *
   * @param int
   *   Entity ID.
   * @param string
   *   Entity type.
   * @param string
   *   Entity bundle.
   */
  public function deleteEntity(int $entity_id, string $entity_type, string $entity_bundle) {
    $id = $entity_type . ':' . $entity_id . ':' . $entity_bundle;
    $data = [
      'id' => $id,
      'namespace' => $entity_type,
    ];

    $event = new DropaiEmbeddingEvent(DropaiEmbeddingEvent::DELETE, $data);
    $this->eventDispatcher->dispatch($event, DropaiEmbeddingEvent::UPDATE);

    $this->loggerFactory->get('dropai')->notice(
      'Removed @id from vector database.',
      ['@id' => $id]
    );
  }

}
