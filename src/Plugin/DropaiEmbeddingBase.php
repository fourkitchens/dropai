<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for DropAI Embedding plugins.
 */
abstract class DropaiEmbeddingBase extends PluginBase implements DropaiEmbeddingInterface, ContainerFactoryPluginInterface {

  /**
   * Models supported by the embedding service as Id/name key/value pairs.
   *
   * @var array
   */
  public $models = [];


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
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an embeddings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \GuzzleHttp\Client $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    Client $httpClient,
    LoggerChannelFactoryInterface $loggerFactory
  ) {
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
    $this->logger = $loggerFactory->get('dropai');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getModels(): array {
    return $this->models;
  }

  /**
   * Validate the selected model.
   *
   * @param string $model
   *   The name of a AI model for this service.
   *
   * @return void
   */
  protected function validateModel(string $model) {
    if(empty($model)) {
      throw new \Exception('A model must be specified to generate embeddings.');
    }
    if(!in_array($model, array_keys($this->models))) {
      throw new \Exception('An invalid model was selected for this service.');
    }
  }

  /**
   * Retrieves the API key from configuration.
   *
   * @param string $config
   *   The name of the configuation object.
   * @param string $key
   *   A string that maps to a key within the config file.
   *
   * @return string
   *   The API key.
   *
   * @throws \Exception
   *   If the API key is not set.
   */
  protected function getApiKey(string $config, string $key): string {
    $apiKey = $this->configFactory->get($config)->get($key);
    if (empty($apiKey)) {
      throw new \Exception('Embedding service API key is not set.');
    }
    return $apiKey;
  }

}
