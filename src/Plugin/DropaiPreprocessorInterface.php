<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for DropAI Preprocessor plugins.
 */
interface DropaiPreprocessorInterface extends PluginInspectionInterface {

  /**
   * Preprocesses the source text string.
   *
   * @param string $text
   *   The source text string to be processed.
   *
   * @return string
   *   The processed text string.
   */
  public function preprocess(string $text): string;

  /**
   * Remove unwanted whitespace from the string.
   *
   * @param string $text
   *   The source text string to be trimmed.
   *
   * @return string
   *   The trimmed text string.
   */
  public function trim(string $source_text): string;

}
