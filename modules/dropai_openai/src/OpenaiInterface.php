<?php

namespace Drupal\dropai_openai;

use Psr\Http\Message\ResponseInterface;

/**
 * Defines an interface for Openai.
 */
interface OpenaiInterface {

  /**
   * Makes the API request to Openai.
   *
   * @param string $apiEndpoint
   *   The API endpoint.
   * @param array $params
   *   The parameters to be added to the request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The API response.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   If the request fails.
   */
  public function makeApiRequest(string $apiEndpoint, array $params): ResponseInterface;

}
