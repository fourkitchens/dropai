<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for DropAI Tokenizer plugins.
 */
abstract class DropaiTokenizerBase extends PluginBase implements DropaiTokenizerInterface {

  /**
   * Models supported by the tokenizer as Id/name key/value pairs.
   *
   * @var array
   */
  public $models = [];

  /**
   * {@inheritdoc}
   */
  public function getModels(): array {
    return $this->models;
  }

}
