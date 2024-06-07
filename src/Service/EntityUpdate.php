<?php

namespace Drupal\dropai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelInterface;

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
   * Constructs a new EntityUpdate object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    LoggerChannelInterface $logger,
  ) {
    $this->configFactory = $configFactory;
    $this->logger = $logger;
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
    $this->logger->notice(
      'Upserted @type @id in vector database.',
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
