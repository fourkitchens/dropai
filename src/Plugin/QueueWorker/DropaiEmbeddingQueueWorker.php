<?php

namespace Drupal\dropai\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dropai\Service\EntityUpdate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'Dropai Embedding Queue Worker' queue worker.
 *
 * @QueueWorker(
 *   id = "dropai_embedding_queue_worker",
 *   title = @Translation("Dropai Embedding Queue Worker"),
 *   cron = {"time" = 300}
 * )
 */
class DropaiEmbeddingQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity update service.
   *
   * @var Drupal\dropai\Service\EntityUpdate
   */
  protected $entityUpdate;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, EntityUpdate $entityUpdate) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityUpdate = $entityUpdate;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('dropai.entity_update')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (isset($data['entity_id']) && isset($data['entity_type'])) {
      $entity = $this->entityTypeManager->getStorage($data['entity_type'])->load($data['entity_id']);
      if (is_null($entity)) {
        return;
      }

      switch ($data['action']) {
        case 'insert':
        case 'update':
          $this->entityUpdate->upsertDocument($entity);
          break;
  
        case 'remove':
          $this->entityUpdate->removeDocument($entity);
          break;
      }
    }
  }

}
