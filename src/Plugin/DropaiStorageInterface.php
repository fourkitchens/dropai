<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for DropAI Storage plugins.
 */
interface DropaiStorageInterface extends PluginInspectionInterface {

  public function upsert($document);

  public function delete($document);

  public function search($query);

  public function createIndex($index);

  public function deleteIndex($index);
}
