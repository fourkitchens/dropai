<?php

namespace Drupal\dropai_openai;

use Drupal\media\MediaInterface;

/**
 * Defines an interface for Openai.
 */
interface OpenaiEntityToTextInterface {

  /**
   * Gets the image as text.
   *
   * @param MediaInterface $entity
   *   The API endpoint.
   *
   * @return string
   *   The extracted text as a string.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   *   If the request fails.
   */
  public function getEntityAsText(MediaInterface $entity): string;

}
