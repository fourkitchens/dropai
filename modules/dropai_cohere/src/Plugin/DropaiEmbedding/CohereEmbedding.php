<?php

namespace Drupal\dropai_cohere\Plugin\DropaiEmbedding;

use Drupal\dropai\Plugin\DropaiEmbeddingBase;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a DropAI Embedding plugin using the Cohere API.
 *
 * @see: https://docs.cohere.com/reference/embed/.
 *
 * @DropaiEmbedding(
 *   id = "cohere",
 *   label = @Translation("Cohere")
 * )
 */
class CohereEmbedding extends DropaiEmbeddingBase {

  public $models = [
    'embed-english-v3.0' => 'embed-english-v3.0',
    'embed-multilingual-v3.0' => 'embed-multilingual-v3.0',
    'embed-english-light-v3.0' => 'embed-english-light-v3.0',
    'embed-multilingual-light-v3.0' => 'embed-multilingual-light-v3.0',
    'embed-english-v2.0' => 'embed-english-v2.0',
    'embed-english-light-v2.0' => 'embed-english-light-v2.0',
    'embed-multilingual-v2.0' => 'embed-multilingual-v2.0',
  ];

  /**
   * {@inheritDoc}
   */
  public function getEmbeddings(array $texts, string $model): array {
    try {
      $this->validateModel($model);
      $apiKey = $this->getApiKey('dropai_cohere.settings', 'api_key');
      $response = $this->makeApiRequest($apiKey, $texts, $model);
      return $this->extractEmbeddings($response);
    }
    catch (\Exception $e) {
      $this->logger->error('An error occurred: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Makes the API request to the Cohere service.
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
    return $this->httpClient->post('https://api.cohere.com/v1/embed', [
      'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'texts' => $texts,
        'model' => $model,
        'input_type' => 'search_document',
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
    if (!isset($data['embeddings'])) {
      throw new \Exception('Invalid response structure: "embeddings" field missing');
    }
    if (empty($data['embeddings'])) {
      throw new \Exception('No embedding values returned for the input text.');
    }
    return $data['embeddings'];
  }

}
