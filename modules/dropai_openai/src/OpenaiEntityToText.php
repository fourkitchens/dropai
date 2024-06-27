<?php

namespace Drupal\dropai_openai;

use Drupal\media\MediaInterface;
use Drupal\Core\Messenger\Messenger;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines the Openai service class.
 */
class OpenaiEntityToText implements OpenaiEntityToTextInterface {

  /**
   * The HTTP client to fetch OpenAI responses.
   *
   * @var \Drupal\dropai_openai\OpenaiInterface
   */
  protected $openai;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * Constructor for Openai Image To Text.
   *
   * @param \Drupal\dropai_openai\OpenaiInterface $openai
   *   The HTTP client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Psr logger.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   */
  public function __construct(OpenaiInterface $openai, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, Messenger $messenger) {
    $this->openai = $openai;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritDoc}
   */
  public function getEntityAsText(MediaInterface $entity): string {
    switch ($entity->bundle()) {
      case 'image':
        return $this->getImageAsText($entity);
      case 'audio':
        return $this->getAudioAsText($entity);
      default:
        return "";
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getImageAsText(MediaInterface $entity): string {

    // $file_id = $entity->field_media_image->target_id;
    // $alt_text = $entity->field_media_image->alt;
    // $file = $this->entityTypeManager->getStorage('file')->load($file_id);
    // $image_contents = file_get_contents($file->getFileUri());
    // $encoded_image = base64_encode($image_contents);

    // try {
    //   $apiEndpoint = "https://api.openai.com/v1/chat/completions";
    //   $messages = [[
    //     "role" => "user",
    //     "content" => [
    //       [
    //         // Media title.
    //         "type" => "text",
    //         "text" => $entity->getName(),
    //       ],
    //       [
    //         // Image alt text.
    //         "type" => "text",
    //         "text" => $alt_text,
    //       ],
    //       [
    //         // Base 64 encoded image.
    //         "type" => "image_url",
    //         "image_url" => [
    //           "url" => "data:image/jpeg;base64,${encoded_image}"
    //         ]
    //       ]
    //     ]
    //   ]];

    //   $params = [
    //     ['name' => 'messages', 'contents' => $messages],
    //     ['name' => 'model', 'contents' => 'gpt-4o'],
    //   ];

    //   $response = $this->openai->makeApiRequest($apiEndpoint, $params);
    //   ksm($response);

    //   //
    //   // return $this->extractImageText($response);
    // }
    // catch (\Exception $e) {
    //   $this->logger->error('An error occurred: ' . $e->getMessage());
    //   return '';
    // }
    return '';


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


  /**
   * {@inheritDoc}
   */
  protected function getAudioAsText(MediaInterface $entity): string {

    $file_id = $entity->field_media_audio_file->target_id;
    $file = $this->entityTypeManager->getStorage('file')->load($file_id);
    $file_contents = file_get_contents($file->getFileUri());

    try {
      $apiEndpoint = "https://api.openai.com/v1/audio/transcriptions";

      $params = [
        ['name' => 'file', 'contents' => $file_contents, 'filename' => $file->getFileUri()],
        ['name' => 'model', 'contents' => 'whisper-1'],
        ['name' => 'language', 'contents' => 'en']
      ];

      $response = $this->openai->makeApiRequest($apiEndpoint, $params);
      return $this->extractAudioText($response);
    }
    catch (\Exception $e) {
      $this->logger->error('An error occurred: ' . $e->getMessage());
      return '';
    }
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
  protected function extractAudioText(ResponseInterface $response): string {
    $data = json_decode($response->getBody()->getContents(), true);
    if (!isset($data['text'])) {
      throw new \Exception('Invalid response structure.');
    }
    return $data['text'];
  }
}
