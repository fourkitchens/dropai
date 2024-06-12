<?php

namespace Drupal\dropai\Plugin\DropaiSplitter;

use Drupal\dropai\Plugin\DropaiSplitterBase;

/**
 * Provides a DropAI Splitter plugin for when you don't feel like splitting.
 *
 * @DropaiSplitter(
 *   id = "none",
 *   label = @Translation("None")
 * )
 */
class NoneSplitter extends DropaiSplitterBase {

  /**
   * {@inheritdoc}
   */
  public function split(string $text, ...$args): array {
    return [$text];
  }

}
