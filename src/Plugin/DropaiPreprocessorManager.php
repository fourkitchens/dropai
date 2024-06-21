<?php

namespace Drupal\dropai\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin manager for DropAI Preprocessor plugins.
 */
class DropaiPreprocessorManager extends DefaultPluginManager {

  /**
   * Constructs a new DropaiPreprocessorManager object.
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
      'Plugin/DropaiPreprocessor',
      $namespaces,
      $module_handler,
      'Drupal\dropai\Plugin\DropaiPreprocessorInterface',
      'Drupal\dropai\Annotation\DropaiPreprocessor'
    );
    $this->alterInfo('dropai_preprocessor_info');
    $this->setCacheBackend($cache_backend, 'dropai_preprocessor_plugins');
  }

  /**
   * Get a list of all plugins as key/value pairs of plugin IDs and labels.
   *
   * @return array
   */
  public function getPluginOptions() {
    $plugin_definitions = $this->getDefinitions();
    if (empty($plugin_definitions)) {
      return ['' => 'No processor plugins detected'];
    }
    $options = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];
    }
    return $options;
  }

}
