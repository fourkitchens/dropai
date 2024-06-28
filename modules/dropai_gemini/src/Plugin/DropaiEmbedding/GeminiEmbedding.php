<?php

namespace Drupal\dropai_gemini\Plugin\DropaiEmbedding;

use Drupal\dropai\Plugin\DropaiEmbeddingBase;
use Psr\Http\Message\ResponseInterface;


/**
 * Provides a DropAI Embedding plugin using the Gemini API.
 *
 * @DropaiEmbedding(
 *   id = "gemini",
 *   label = @Translation("Google Gemini")
 * )
 */
class GeminiEmbedding extends DropaiEmbeddingBase {

  public $models = [
    'embedding-001' => 'Embedding 001',
    'text-embedding-004' => 'Text Embedding 004',
  ];
  /**
   * {@inheritDoc}
   */
  public function getEmbeddings(array $texts, string $model): array {
    try {
      $this->validateModel($model);
      $apiKey = $this->getApiKey('dropai_gemini.settings', 'api_key');
      $embeddings = [];
      foreach ($texts as $text) {
        $response = $this->makeApiRequest($apiKey, $text, $model);
        $embeddings[] = $this->extractEmbeddings($response);
      }
      return $embeddings;
    }
    catch (\Exception $e) {
      $this->logger->error('An error occurred: ' . $e->getMessage());
      $this->messenger->addError($e->getMessage());
      return [];
    }
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
  protected function makeApiRequest(string $apiKey, string $text, string $model) {
    $apiEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent";
    return $this->httpClient->post($apiEndpoint, [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'query' => [
        'key' => $apiKey,
      ],
      'json' => [
        'model' => "models/{$model}",
        'content' => [
          'parts' => ['text' => $text],
        ],
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
    if (!isset($data['embedding']['values'])) {
      throw new \Exception('Invalid response structure.');
    }
    return $data['embedding']['values'];
  }

}
