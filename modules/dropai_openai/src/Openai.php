<?php

namespace Drupal\dropai_openai;

use Drupal\Core\Messenger\Messenger;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\media\MediaInterface;

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

  public function getTextFromImage(MediaInterface $entity): string {
    $file_id = $entity->field_media_image->target_id;
    $alt_text = $entity->field_media_image->alt;
    $file = \Drupal::service('entity_type.manager')->getStorage('file')->load($file_id);
    $image_contents = file_get_contents($file->getFileUri());
    $encoded_image = base64_encode($image_contents);

    $apiEndpoint = "https://api.openai.com/v1/chat/completions";
    $headers = [
      'Content-Type' => 'application/json',
      'Authorization' => 'Bearer ' . $this->getApiKey(),
    ];
    $payload = [
      'model' => 'gpt-4o',
      'max_tokens' => 300,
      'messages' => [
        [
          'role' => 'user',
          'content' => [
            [
              'type' => 'text',
              'text' => 'What is in this image?',
            ],
            [
              'type' => 'image_url',
              'image_url' => [
                'url' => 'data:image/jpeg;base64,' . $encoded_image,
              ],
            ],
          ],
        ],
      ],
    ];
    $response = $this->httpClient->post($apiEndpoint, [
      'headers' => $headers,
      'json' => $payload,
    ]);

    return $this->extractImageText($response);

  }

      /**
   * Extracts embeddings from the response data.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The API response.
   *
   * @return string
   *   The extrated text as a string.
   *
   * @throws \Exception
   *   If no embeddings are found.
   */
  protected function extractImageText(ResponseInterface $response): string {
    $data = json_decode($response->getBody()->getContents(), true);
    if (!isset($data['choices'])) {
      throw new \Exception('Invalid response structure.');
    }
    $content = array_map(function($item) {
      return $item['message']['content'];
    }, $data['choices']);
    return implode(', ', $content);
  }

}
