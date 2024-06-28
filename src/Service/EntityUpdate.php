<?php

namespace Drupal\dropai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\dropai\Plugin\DropaiStorageManager;

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
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The DropAI storage plugin manager service.
   *
   * @var \Drupal\dropai\Plugin\DropaiStorageManager
   */
  protected $dropaiStorageManager;

  /**
   * Constructs a new EntityUpdate object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   * @param \Drupal\dropai\Plugin\DropaiStorageManager $dropaiStorageManager
   *    The DropAI storage plugin manager service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    LoggerChannelInterface $logger,
    DropaiStorageManager $dropaiStorageManager
  ) {
    $this->configFactory = $configFactory;
    $this->logger = $logger;
    $this->dropaiStorageManager = $dropaiStorageManager;
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
   * Upsert document in vector database.
   *
   * Used to insert or update content into the vector database when it has been
   * added or updated in Drupal. This method fires on hook_entity_insert() and
   * hook_entity_update().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity in Drupal.
   */
  public function upsertDocument(EntityInterface $entity) {
    try {
      $this->getCofiguredStoragePlugin()->upsert($entity);
    } catch (\Exception $e) {
      $this->logger->error(
        'Failed to upsert @type @id in vector database: @error',
        [
          '@type' => $entity->getEntityTypeId(),
          '@id' => $entity->id(),
          '@error' => $e->getMessage(),
        ]
      );
    }

    $this->logger->notice(
      'Attempted to upsert @type @id in vector database.',
      ['@type' => $entity->getEntityTypeId(), '@id' => $entity->id()]
    );
  }

  /**
   * Remove a document from the vector database.
   *
   * Used to remove content from the vector database when it has been deleted
   * from Drupal. This method fires on hook_entity_delete().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity in Drupal.
   */
  public function removeDocument(EntityInterface $entity) {
    $this->logger->notice(
      'Removed @type @id from vector database.',
      ['@type' => $entity->getEntityTypeId(), '@id' => $entity->id()]
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
