<?php

namespace Drupal\dropai_atlas\Plugin\DropaiStorage;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dropai\Plugin\DropaiStorageBase;
use MongoDB\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DropAI Storage plugin for interacting with Atlas.
 *
 * @DropaiStorage(
 *   id = "atlas",
 *   label = @Translation("Atlas")
 * )
 */
class AtlasStorage extends DropaiStorageBase implements ContainerFactoryPluginInterface {

  /**
   * The database service.
   *
   * @var \MongoDB\Database
   */
  protected Database $database;

  /**
   * Constructs a new AtlasStorage object.
   *
   * @param \MongoDB\Database $database
   *   The connection service.
   */
  public function __construct(array $configuration, $pluginId, array $pluginDefinition, Database $database) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->database = $database;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('dropai_atlas.mongo_storage')
    );
  }

  public function upsert($document):void {
    $this->database->selectCollection('documents')->updateOne(
      ['_id' => 1],
      ['$set' => ['title' => 'booger']],
      ['upsert' => TRUE]);
  }

  public function delete($document) {
    // TODO: Implement delete() method.
  }

  public function search($query) {
    // TODO: Implement search() method.
  }

  public function createIndex($index) {
    // TODO: Implement createIndex() method.
  }

  public function deleteIndex($index) {
    // TODO: Implement deleteIndex() method.
  }

  protected function getCollection() {

  }

}
