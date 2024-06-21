<?php

namespace Drupal\dropai_gemini\Plugin\DropaiEmbedding;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\dropai\Plugin\DropaiEmbeddingBase;
use Gemini;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DropAI Embedding plugin using the OpenAI API.
 *
 * @DropaiEmbedding(
 *   id = "gemini",
 *   label = @Translation("Google Gemini")
 * )
 */
class GeminiEmbedding extends DropaiEmbeddingBase implements ContainerFactoryPluginInterface {

  public $models = [
    'embedding-001' => 'Embedding 001',
    'text-embedding-004' => 'Text Embedding 004',
  ];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a GeminiEmbedding object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory) {
      $this->configFactory = $configFactory;
      $this->logger = $loggerFactory->get('DropAI');
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
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getEmbeddings(string $text, string $model = 'embedding-001'): array {
    $config = $this->configFactory->get('dropai_gemini.settings');
    $apiKey = $config->get('api_key');
    if (empty($apiKey)) {
      $this->logger->error('Gemini API key is not configured.');
      return [];
    }
    $client = Gemini::client($apiKey);
    $response = $client
      ->embeddingModel("models/{$model}")
      ->embedContent("Write a story about a magic backpack.");
      if (empty($response->embedding->values)) {
        $this->logger->error('No embedding values returned for the input text');
        return [];
      }
      return $response->embedding->values;
  }

}
