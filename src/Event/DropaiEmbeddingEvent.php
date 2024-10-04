<?php

namespace Drupal\dropai\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Class to define a Event Dispatcher.
 */
class DropaiEmbeddingEvent extends Event {

  const INSERT = 'dropai.embedding.insert';
  const UPDATE = 'dropai.embedding.update';
  const DELETE = 'dropai.embedding.delete';

  /**
   * The data.
   * 
   * @var array
   */
  protected $data;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * Return the data.
   **/
  public function getData() {
    return $this->data;
  }

}
