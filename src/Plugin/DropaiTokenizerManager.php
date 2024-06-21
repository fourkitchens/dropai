<?php

namespace Drupal\dropai\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin manager for DropAI Tokenizer plugins.
 */
class DropaiTokenizerManager extends DefaultPluginManager {

  /**
   * Constructs a new DropaiTokenizerManager object.
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
      'Plugin/DropaiTokenizer',
      $namespaces,
      $module_handler,
      'Drupal\dropai\Plugin\DropaiTokenizerInterface',
      'Drupal\dropai\Annotation\DropaiTokenizer'
    );
    $this->alterInfo('dropai_tokenizer_info');
    $this->setCacheBackend($cache_backend, 'dropai_tokenizer_plugins');
  }

  /**
   * Get a list of all plugins as key/value pairs of plugin IDs and labels.
   *
   * @return array
   */
  public function getPluginOptions() {
    $plugin_definitions = $this->getDefinitions();
    if (empty($plugin_definitions)) {
      return ['' => 'No tokenizer plugins detected'];
    }
    $options = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];
    }
    return $options;
  }

}
