<?php

namespace Drupal\dropai_openai\Plugin\DropaiEmbedding;

use Drupal\dropai\Plugin\DropaiEmbeddingBase;
use Psr\Http\Message\ResponseInterface;


/**
 * Provides a DropAI Embedding plugin using the OpenAI API.
 *
 * @DropaiEmbedding(
 *   id = "openai",
 *   label = @Translation("OpenAI")
 * )
 */
class OpenaiEmbedding extends DropaiEmbeddingBase {

  public $models = [
    'text-embedding-3-large' => 'text-embedding-3-large',
    'text-embedding-3-small' => 'text-embedding-3-small',
    'text-embedding-ada-002' => 'text-embedding-ada-002',
  ];

  /**
   * {@inheritDoc}
   */
  public function getEmbeddings(array $texts, string $model): array {
    try {
      $this->validateModel($model);
      $apiKey = $this->getApiKey('dropai_openai.settings', 'api_key');
      $response = $this->makeApiRequest($apiKey, $texts, $model);
      return $this->extractEmbeddings($response);
    }
    catch (\Exception $e) {
      $this->logger->error('An error occurred: ' . $e->getMessage());
      $this->messenger->addError($e->getMessage());
    }
    return [];
  }

  /**
   * Makes the API request to the VoyageAI service.
   *
   * @param string $apiKey
   *   The API key.
   * @param array $texts
   *   The texts to be embedded.
   * @param string $model
   *   The model to use for embeddings.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The API response.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   If the request fails.
   */
  protected function makeApiRequest(string $apiKey, array $texts, string $model) {
    $apiEndpoint = "https://api.openai.com/v1/embeddings";
    return $this->httpClient->post($apiEndpoint, [
      'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
      ],
        'json' => [
          'input' => $texts,
          'model' => $model,
      ],
    ]);
  }

  /**
   * Extracts embeddings from the response data.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The API response.
   *
   * @return array
   *   The embeddings values.
   *
   * @throws \Exception
   *   If no embeddings are found.
   */
  protected function extractEmbeddings(ResponseInterface $response): array {
    $data = json_decode($response->getBody()->getContents(), true);
    if (!isset($data['data'])) {
      throw new \Exception('Invalid response structure.');
    }
    return array_map(function($item) {
      return $item['embedding'];
    }, $data['data']);
  }

}
