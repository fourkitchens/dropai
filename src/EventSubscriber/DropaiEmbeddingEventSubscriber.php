<?php

namespace Drupal\dropai\EventSubscriber;

use Drupal\dropai\Event\DropaiEmbeddingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Events Subscriber for Embedding Dropai.
 */
abstract class DropaiEmbeddingEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      DropaiEmbeddingEvent::INSERT => ['onDropaiEmbeddingInsert'],
      DropaiEmbeddingEvent::UPDATE => ['onDropaiEmbeddingUpdate'],
      DropaiEmbeddingEvent::DELETE => ['onDropaiEmbeddingDelete'],
    ];
  }

  /**
   * Method called when the Embedding is inserted
   *
   * @param array $data.
   */
  public function onDropaiEmbeddingInsert(DropaiEmbeddingEvent $event) {

  }

  /**
   * Method called when the Embedding is updated
   *
   * @param array $data.
   */
  public function onDropaiEmbeddingUpdate(DropaiEmbeddingEvent $event) {

  }

  /**
   * Method called when the Embedding is delete
   *
   * @param array $data.
   */
  public function onDropaiEmbeddingDelete(DropaiEmbeddingEvent $event) {

  }

}
