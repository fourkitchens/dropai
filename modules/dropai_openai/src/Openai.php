<?php

namespace Drupal\dropai_openai;

use Drupal\Core\Messenger\Messenger;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines the Openai service class.
 */
class Openai implements OpenaiInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client to fetch OpenAI responses.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Psr logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected Messenger $messenger;


  /**
   * Constructor for Openai.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Psr logger.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $http_client, LoggerInterface $logger, Messenger $messenger) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * Retrieves the API key from configuration.
   *
   * @return string
   *   The API key.
   *
   * @throws \Exception
   *   If the API key is not set.
   */
  protected function getApiKey(): string {
    $apiKey = $this->configFactory->get('dropai_openai.settings')->get('api_key');
    if (empty($apiKey)) {
      throw new \Exception('OpenAI API key is not set.');
    }
    return $apiKey;
  }

  /**
   * @inheritDoc
   */
  public function makeApiRequest(string $apiEndpoint, array $params): ResponseInterface {
    $apiKey = $this->getApiKey();

    return $this->httpClient->post($apiEndpoint, [
      'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
      ],
      'multipart' => $params,
    ]);
  }
}
