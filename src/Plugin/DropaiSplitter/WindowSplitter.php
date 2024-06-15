<?php

namespace Drupal\dropai\Plugin\DropaiSplitter;

use Drupal\dropai\Plugin\DropaiSplitterBase;

/**
 * Provides a DropAI Splitter plugin to break content by paragraphs.
 *
 * @DropaiSplitter(
 *   id = "window",
 *   label = @Translation("Sliding Window")
 * )
 */
class WindowSplitter extends DropaiSplitterBase {

  /**
   * {@inheritdoc}
   */
  public function split(string $text, ...$args): array {
    $chunks = [];
    $length = strlen($text);
    $overlap = (int) round($args['maxSize'] * ($args['overlap'] / 100));
    $i = 0;
    while ($i < $length) {
        $end = min($i + $args['maxSize'], $length);
        $chunk = substr($text, $i, $end - $i);
        $chunks[] = $chunk;
        $i += $args['maxSize'] - $overlap;
    }
    return $chunks;
  }

}
