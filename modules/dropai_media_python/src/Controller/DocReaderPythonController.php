<?php

namespace Drupal\dropai_media_python\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a controller for the Document Reader pages.
 */
class DocReaderPythonController extends ControllerBase {

  /**
   * The HTTP client to fetch the URL.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The FileSystem Interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new DocReaderFactory object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The FileSystem Interface.
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle HTTP Client.
   */
  public function __construct(FileSystemInterface $fileSystem, Client $http_client) {
    $this->fileSystem = $fileSystem;
    $this->httpClient = $http_client;
  }

  /**
   * Constructs a new DocReaderFactory object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Container Interface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('http_client'),
    );
  }

  /**
   * This function render the content of a document.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media entity.
   */
  public function content(Media $media) {
    $content = '';
    $title = '';
    $field_name = 'field_media_document';

    if ($media->bundle() === 'document' && $media->hasField($field_name) && !$media->get($field_name)->isEmpty()) {
      $file = $media->get($field_name)->entity;
      $title = $file->label();
      $filePath = $this->fileSystem->realpath($file->getFileUri());

      if (file_exists($filePath)) {
        $content = $this->getDocumentContent($filePath);
      }
      else {
        $content = $this->t('The file does not exists.');
      }
    }

    return [
      '#title' => $this->t('Document Content via Python: @title', ['@title' => $title]),
      'content' => [
        '#type' => 'markup',
        '#markup' => $content,
      ],
    ];
  }

  private function getDocumentContent($path) {
    $url = 'http://python.lndo.site/document-to-text';
    try {
      $response = $this->httpClient->post($url, [
        'json' => [
          'path' => $path,
        ],
      ],);
      $body = json_decode($response->getBody()->__toString(), TRUE)['data'];
    }
    catch (\Exception $e) {
      $body = $this->t('An error occurred: @message', ['@message' => $e->getMessage()]);
    }
    return $body;
  }

}
