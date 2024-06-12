<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for DropAI Tokenizer plugins.
 */
interface DropaiTokenizerInterface extends PluginInspectionInterface {

  /**
   * Get a list of all models supported by the tokenizer.
   *
   * Models are returned as key/value pairs that can be used in a select list.
   *
   * @return array
   *   ID/Name as key/value pairs describing available models.
   */
  public function getModels(): array;

  /**
   * Remove unwanted whitespace from the string.
   *
   * @param string $text
   *   The source text string to be tokenized.
   * @param string $model
   *   The name of the model to use when calculating tokens.
   *
   * @return array
   *   An array of numberic token values.
   */
  public function tokenize(string $text, string $model): array;

}
