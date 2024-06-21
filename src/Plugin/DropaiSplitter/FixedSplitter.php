<?php

namespace Drupal\dropai\Plugin\DropaiSplitter;

use Drupal\dropai\Plugin\DropaiSplitterBase;

/**
 * Provides a DropAI Splitter plugin to break content to a fixed size.
 *
 * @DropaiSplitter(
 *   id = "fixed",
 *   label = @Translation("Fixed (preserve words)")
 * )
 */
class FixedSplitter extends DropaiSplitterBase {

  /**
   * {@inheritdoc}
   */
  public function split(string $text, ...$args): array {
    return explode("%%%", wordwrap($text, $args['maxSize'], "%%%", false));
  }

}
