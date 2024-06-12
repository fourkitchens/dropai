<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for DropAI Preprocessor plugins.
 */
abstract class DropaiPreprocessorBase extends PluginBase implements DropaiPreprocessorInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocess(string $text): string {
    // Default implementation, can be overridden by plugins.
    return $this->trim($text);
  }

  /**
   * {@inheritdoc}
   */
  public function trim(string $text): string {
    $text = preg_replace("/[\r\n]+/", "\n", $text);
    $text = trim($text);
    return $text;
  }

}
