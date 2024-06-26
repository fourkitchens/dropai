<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for DropAI Embedding plugins.
 */
interface DropaiEmbeddingInterface extends PluginInspectionInterface {

  /**
   * Get a list of all models supported by the embedding service.
   *
   * Models are returned as key/value pairs that can be used in a select list.
   *
   * @return array
   *   ID/Name as key/value pairs describing available models.
   */
  public function getModels(): array;

  /**
   * Convert a string into a set of vector embeddings.
   *
   * @param string $text
   *   The source text string to be vectorized.
   * @param string $model
   *   The name of the model to use when creating the embeddings.
   *
   * @return array
   *   TBD.
   */
  // public function getEmbedding(string $text, string $model): array;

  /**
   * Convert an array of text string into a set of vector embeddings.
   *
   * @param string[] $pieces
   *   An array of text strings to be vectorized.
   * @param string $model
   *   The name of the model to use when creating the embeddings.
   *
   * @return array
   *   TBD.
   */
  public function getEmbeddings(array $pieces, string $model): array;

}
