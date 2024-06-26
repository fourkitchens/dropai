<?php

namespace Drupal\dropai\Plugin;

/**
 * Defines an interface for DropAI Embedding Providers.
 */
interface DropaiEmbeddingProviderInterface {

  /**
   * This function add or update a record. 
   *
   * @param array $data
   * @return void
   */
  public function upsert(array $data): void;

  /**
   * This function delete a record. 
   *
   * @param string $item_id
   * @return void
   */
  public function delete(string $item_id): void;

}
