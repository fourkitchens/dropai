<?php

namespace Drupal\dropai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\Entity\Node;
use Drupal\dropai\Plugin\DropaiEmbeddingManager;
use Drupal\dropai\Plugin\DropaiSplitterManager;
use Drupal\dropai\Plugin\DropaiPreprocessorManager;
use Drupal\dropai\Plugin\DropaiLoaderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    DropaiEmbeddingManager $dropai_embedding_manager,
    DropaiLoaderManager $dropai_loader_manager,
    DropaiPreprocessorManager $dropai_preprocessor_manager,
    DropaiSplitterManager $dropai_splitter_manager,
  ) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->dropaiEmbeddingManager = $dropai_embedding_manager;
    $this->dropaiLoaderManager = $dropai_loader_manager;
    $this->dropaiPreprocessorManager = $dropai_preprocessor_manager;
    $this->dropaiSplitterManager = $dropai_splitter_manager;
  }

  /**
   * Create function.
   *
   * @param ContainerInterface $container
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.dropai_embedding'),
      $container->get('plugin.manager.dropai_loader'),
      $container->get('plugin.manager.dropai_preprocessor'),
      $container->get('plugin.manager.dropai_splitter'),
    );
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
   */
  public function upsertEntiy(EntityInterface $entity) {
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

    // Render the vector.
    $vector = array_map(function($embedding) {
      return json_encode($embedding);
    }, $embeddings);

    // Set the metadata values.
    $metadata = [];
    $metadata['entity_type'] = $entity->getEntityTypeId();
    $metadata['entity_id'] = $entity->id();
    $metadata['updated'] = $entity->getChangedTime();
    // Add more fields if the entity is a Node.
    if ($entity instanceof Node) {
      $metadata['source_url'] = $entity->toUrl()->toString();
      $metadata['status'] = $entity->isPublished();
      $metadata['title'] = $entity->getTitle();
      $metadata['content'] = '';
    }
    // Render the data.
    $data = [
      'id' => $entity->getEntityTypeId() . '-' . $entity->id(),
      'values' => $vector[0],
      'metadata' => $metadata,
    ];

    $this->loggerFactory->get('dropai')->notice(
      'Upserted @type @id in vector database: @vector',
      [
        '@type' => $entity->getEntityTypeId(),
        '@id' => $entity->id(),
        '@vector' => '<pre>' . print_r($data, 1) . '</pre>',
      ]
    );
  }

  /**
   * Remove an entity from the vector database.
   *
   * Used to remove content from the vector database when it has been deleted
   * from Drupal. This method fires on hook_entity_delete().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity in Drupal.
   */
  public function deleteEntity(int $entity_id, string $entity_type) {
    $this->loggerFactory->get('dropai')->notice(
      'Removed @type @id from vector database.',
      ['@type' => $entity_type, '@id' => $entity_id]
    );
  }

  /**
   * Add all documents to the vector database.
   */
  public function addAllDocuments() { }

  /**
   * Delete all documents from the vector database.
   */
  public function removeAllDocuments() { }

}
