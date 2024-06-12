<?php

namespace Drupal\dropai\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin manager for DropAI Splitter plugins.
 */
class DropaiSplitterManager extends DefaultPluginManager {

  /**
   * Constructs a new DropaiSplitterManager object.
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
      'Plugin/DropaiSplitter',
      $namespaces,
      $module_handler,
      'Drupal\dropai\Plugin\DropaiSplitterInterface',
      'Drupal\dropai\Annotation\DropaiSplitter'
    );
    $this->alterInfo('dropai_splitter_info');
    $this->setCacheBackend($cache_backend, 'dropai_splitter_plugins');
  }

  /**
   * Get a list of all plugins as key/value pairs of plugin IDs and labels.
   *
   * @return array
   */
  public function getPluginOptions() {
    $plugin_definitions = $this->getDefinitions();
    $options = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];
    }
    return $options;
  }

}
