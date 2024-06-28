<?php

namespace Drupal\dropai_voyageai\Plugin\DropaiEmbedding;

use Drupal\dropai\Plugin\DropaiEmbeddingBase;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a DropAI Embedding plugin using the VoyageAI API.
 *
 * @see: https://docs.voyageai.com/reference/embeddings-api.
 *
 * @DropaiEmbedding(
 *   id = "voyageai",
 *   label = @Translation("VoyageAI")
 * )
 */
class VoyageAiEmbedding extends DropaiEmbeddingBase {

  public $models = [
    'voyage-2' => 'voyage-2',
    'voyage-large-2' => 'voyage-large-2',
    'voyage-finance-2' => 'voyage-finance-2',
    'voyage-multilingual-2' => 'voyage-multilingual-2',
    'voyage-law-2' => 'voyage-law-2',
    'voyage-code-2' => 'voyage-code-2',
  ];

  /**
   * {@inheritDoc}
   */
  public function getEmbeddings(array $texts, string $model): array {
    try {
      $this->validateModel($model);
      $apiKey = $this->getApiKey('dropai_voyageai.settings', 'api_key');
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
    return $this->httpClient->post('https://api.voyageai.com/v1/embeddings', [
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
