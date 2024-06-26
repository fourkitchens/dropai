<?php

namespace Drupal\dropai\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dropai\Service\EntityUpdate;
use Drupal\dropai\Traits\DropaiEmbeddingTrait;
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

  use DropaiEmbeddingTrait;

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
      // Check if the entity allows indexing.
      if ($this->dropai_check_entity_allows_indexing($data['entity_type'], $data['entity_bundle'])) {
        switch ($data['action']) {
          case 'insert':
          case 'update':
            $entity = $this->entityTypeManager->getStorage($data['entity_type'])->load($data['entity_id']);
            if (is_null($entity)) {
              return;
            }
            $this->entityUpdate->upsertEntiy($entity);
            break;

          case 'remove':
            $this->entityUpdate->deleteEntity($data['entity_id'], $data['entity_type']);
            break;
        }
      }
    }
  }

}
