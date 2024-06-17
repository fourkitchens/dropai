<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for DropAI Embedding plugins.
 */
abstract class DropaiEmbeddingBase extends PluginBase implements DropaiEmbeddingInterface {

  /**
   * Models supported by the embedding service as Id/name key/value pairs.
   *
   * @var array
   */
  public $models = [];

  /**
   * {@inheritdoc}
   */
  public function getModels(): array {
    return $this->models;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmbeddingsMultiple(array $items, string $model = 'embedding-001'): array {
    $vectors = [];
    foreach ($items as $item) {
      $vectors[] = $this->getEmbeddings($item, $model);
    }
    return $vectors;
  }

}
