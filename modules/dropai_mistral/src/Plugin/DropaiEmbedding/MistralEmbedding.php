<?php

namespace Drupal\dropai_mistral\Plugin\DropaiEmbedding;

use Drupal\dropai\Plugin\DropaiEmbeddingBase;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a DropAI Embedding plugin using the mistral API.
 *
 * @see: https://docs.mistral.ai/capabilities/embeddings/.
 *
 * @DropaiEmbedding(
 *   id = "mistral",
 *   label = @Translation("Mistral")
 * )
 */
class MistralEmbedding extends DropaiEmbeddingBase {

  public $models = [
    'mistral-embed' => 'mistral-embed',
  ];

  /**
   * {@inheritDoc}
   */
  public function getEmbeddings(array $texts, string $model): array {
    try {
      $this->validateModel($model);
      $apiKey = $this->getApiKey('dropai_mistral.settings', 'api_key');
      $response = $this->makeApiRequest($apiKey, $texts, $model);
      return $this->extractEmbeddings($response);
    }
    catch (\Exception $e) {
      $this->logger->error('An error occurred: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Makes the API request to the MistralAi service.
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
    return $this->httpClient->post('https://api.mistral.ai/v1/embeddings', [
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
      throw new \Exception('Invalid response structure: "data" field missing');
    }
    $embeddings = array_map(function($item) {
      return $item['embedding'];
    }, $data['data']);
    if (empty($embeddings)) {
      throw new \Exception('No embedding values returned for the input text.');
    }
    return $embeddings;
  }

}
