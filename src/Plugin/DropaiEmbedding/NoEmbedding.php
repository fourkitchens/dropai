<?php

namespace Drupal\dropai\Plugin\DropaiEmbedding;

use Drupal\dropai\Plugin\DropaiEmbeddingBase;

/**
 * Provides a DropAI Embedding plugin using the OpenAI API.
 *
 * @DropaiEmbedding(
 *   id = "none",
 *   label = @Translation("None")
 * )
 */
class NoEmbedding extends DropaiEmbeddingBase {

  /**
   * {@inheritDoc}
   */
  public function getEmbeddings(string $text, string $model = ''): array {
    return [];
  }

}
