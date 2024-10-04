<?php

namespace Drupal\dropai_pinecone\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\dropai_pinecone\Service\PineconeEmbeddingProvider;
use Drupal\dropai\Event\DropaiEmbeddingEvent;
use Drupal\dropai\EventSubscriber\DropaiEmbeddingEventSubscriber;

/**
 * Create a service for Pinecone Provider.
 */
class PineconeEmbeddingEventSubscriber extends DropaiEmbeddingEventSubscriber  {

  /**
   * The Pinecone Embedding Provider.
   *
   * @var \Drupal\dropai_pinecone\Service\PineconeEmbeddingProvider
   */
  protected $pineconeEmbeddingProvider;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new Embedding Provider object.
   *
   * @param \Drupal\dropai_pinecone\Service\PineconeEmbeddingProvider
   *   The Pinecone Embedding Provider.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger Factory.
   */
  public function __construct(
    PineconeEmbeddingProvider $pinecone_embedding_provider,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->pineconeEmbeddingProvider = $pinecone_embedding_provider;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritDoc}
   */
  public function onDropaiEmbeddingInsert(DropaiEmbeddingEvent $event) {
    $data = $event->getData();

    if (!empty($data)) {
      $this->pineconeEmbeddingProvider->upsert($data);
    }
    else {
      $this->loggerFactory->get('dropai_pinecone')->notice(
        'The data is empty.',
      );
    }
  }

  /**
   * {@inheritDoc}
   */
  public function onDropaiEmbeddingUpdate(DropaiEmbeddingEvent $event) {
    $data = $event->getData();

    if (!empty($data)) {
      $this->pineconeEmbeddingProvider->upsert($data);
    }
    else {
      $this->loggerFactory->get('dropai_pinecone')->notice(
        'The data is empty.',
      );
    }
  }

  /**
   * {@inheritDoc}
   */
  public function onDropaiEmbeddingDelete(DropaiEmbeddingEvent $event) {
    $data = $event->getData();

    if (!empty($data) && isset($data['id'])) {
      $this->pineconeEmbeddingProvider->delete($data['id']);
    }
    else {
      $this->loggerFactory->get('dropai_pinecone')->notice(
        'The entity id is empty.',
      );
    }
  }

}
