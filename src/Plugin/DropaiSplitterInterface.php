<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for DropAI Splitter plugins.
 */
interface DropaiSplitterInterface extends PluginInspectionInterface {

  /**
   * Splits text into multiple text segments.
   *
   * @param string $text
   *   The source text string to be split.
   * @param mixed $args
   *   Parameters that may be required by the splitting method.
   *
   * @return array
   *   An array of text segments derived from the source text.
   */
  public function split(string $text, ...$args): array;

}
