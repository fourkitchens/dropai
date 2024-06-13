<?php

namespace Drupal\dropai\Plugin\DropaiSplitter;

use Drupal\dropai\Plugin\DropaiSplitterBase;

/**
 * Provides a DropAI Splitter plugin to break content by paragraphs.
 *
 * Each paragraph becomes a chunk. Paragraphs longer than the maxSize are split
 * into multiple chunks.
 *
 * @DropaiSplitter(
 *   id = "paragraph",
 *   label = @Translation("Paragraph")
 * )
 */
class ParagraphSplitter extends DropaiSplitterBase {

  /**
   * {@inheritdoc}
   */
  public function split(string $text, ...$args): array {
    $chunks = [];
    foreach (explode("\n", $text) as $paragraph) {
      if (empty(trim($paragraph))) continue;
      $length = strlen($paragraph);
      for ($i = 0; $i < $length; $i += $args['maxSize']) {
          $chunks[] = substr($paragraph, $i, $args['maxSize']);
      }
    }
    return $chunks;
  }

}
