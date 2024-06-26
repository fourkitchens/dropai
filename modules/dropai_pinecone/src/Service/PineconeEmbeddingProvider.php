<?php

namespace Drupal\dropai_pinecone\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\dropai\Plugin\DropaiEmbeddingProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a service for Pinecone Provider.
 */
class PineconeEmbeddingProvider implements DropaiEmbeddingProviderInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The Pinecone Api Key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * The Pinecone Index Host.
   *
   * @var string
   */
  protected $indexHost;

  /**
   * Constructs a new Embedding Provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger Factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $pineconeConfig = $this->configFactory->get('dropai_pinecone.settings');
    $this->apiKey = $pineconeConfig->get('pinecone_api_key');
    $this->indexHost = $pineconeConfig->get('pinecone_index');
  }

  /**
   * Provide a factory class to create a PDF Reader.
   */
  public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function upsert(array $data): void {
    if (empty($this->apiKey) || empty($this->indexHost)) {
      $this->loggerFactory->get('dropai_pinecone')->error('The Pincone module has not yet been configured.');
      return;
    }

    $vectors = ['vectors' => $data];
    $this->pineconeUpsert($vectors);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $item_id): void {
    if (empty($this->apiKey) || empty($this->indexHost)) {
      $this->loggerFactory->get('dropai_pinecone')->error('The Pincone module has not yet been configured.');
      return;
    }
  }

  /**
   * This function send a request to Pinecone Service.
   */
  private function pineconeUpsert($data) {
    $ch = curl_init($this->indexHost . '/vectors/upsert');

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Api-Key: ' . $this->apiKey,
      'Content-Type: application/json'
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
      $this->loggerFactory->get('dropai_pinecone')->error(
        'There is an error: "@error"',
        ['@error' => curl_error($ch)]
      );
    }
    else {
      $this->loggerFactory->get('dropai_pinecone')->notice('<pre>' . print_r($response, 1) . '<pre>');
    }

    curl_close($ch);
  }

}
