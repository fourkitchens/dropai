<?php

namespace Drupal\dropai\Plugin\DropaiEmbedding;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dropai\Plugin\DropaiEmbeddingBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DropAI Embedding plugin using the OpenAI API.
 *
 * @DropaiEmbedding(
 *   id = "openai",
 *   label = @Translation("OpenAI")
 * )
 */
class OpenAiEmbedding extends DropaiEmbeddingBase implements ContainerFactoryPluginInterface {

  public $models = [
    'text-embedding-3-small' => 'text-embedding-3-small',
    'text-embedding-3-large' => 'text-embedding-3-large',
    'text-embedding-ada-002' => 'text-embedding-ada-002',
  ];

  /**
   * The HTTP client to fetch OpenAI responses.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new OpenAiEmbedding object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    Client $httpClient
  ) {
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getEmbeddings(string $text, string $model = 'text-embedding-ada-002'): array {
    // $api_key = 'key'; // Replace with your OpenAI API key
    // $response = $this->httpClient->post('https://api.openai.com/v1/embeddings', [
    //   'headers' => [
    //     'Authorization' => 'Bearer ' . $api_key,
    //     'Content-Type' => 'application/json',
    //   ],
    //   'json' => [
    //     'input' => $text,
    //     'model' => $model,
    //   ],
    // ]);
    // $data = json_decode($response->getBody(), true);
    // return $data['data'][0]['embedding'] ?? [];
    return [];
  }

}
