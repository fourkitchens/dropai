<?php

namespace Drupal\dropai\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin manager for DropAI Loader plugins.
 */
class DropaiLoaderManager extends DefaultPluginManager {

  /**
   * Constructs a new DropaiLoaderManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/DropaiLoader',
      $namespaces,
      $module_handler,
      'Drupal\dropai\Plugin\DropaiLoaderInterface',
      'Drupal\dropai\Annotation\DropaiLoader'
    );
    $this->alterInfo('dropai_loader_info');
    $this->setCacheBackend($cache_backend, 'dropai_loader_plugins');
  }

  /**
   * Get a list of all plugins as key/value pairs of plugin IDs and labels.
   *
   * @return array
   */
  public function getPluginOptions(EntityInterface $entity) {
    $plugin_definitions = $this->getDefinitions();
    if (empty($plugin_definitions)) {
      return ['' => 'No loader plugins detected'];
    }
    $options = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      if($plugin_definition['class']::applies($entity)) {
        $options[$plugin_id] = $plugin_definition['label'];
      }
    }
    return $options;
  }

  /**
   * Loads a DropAI loader plugin based on the MIME type.
   *
   * @param string $mime_type
   *   The MIME type to match.
   *
   * @return \Drupal\dropai\Plugin\DropaiLoaderInterface|null
   *   The matched DropAI loader plugin, or NULL if no match is found.
   */
  public function loadPluginByMimeType(string $mime_type): ?DropaiLoaderInterface {
    $plugin_definitions = $this->getDefinitions();
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      if (isset($plugin_definition['mime_type']) && $plugin_definition['mime_type'] === $mime_type) {
        return $this->createInstance($plugin_id);
      }
    }
    return NULL;
  }

}
