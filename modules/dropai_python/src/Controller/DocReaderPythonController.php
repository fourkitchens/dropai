<?php

namespace Drupal\dropai_python\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $pythonConfig;

  /**
   * Constructs a new DocReaderFactory object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The FileSystem Interface.
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle HTTP Client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory interface.
   */
  public function __construct(
    FileSystemInterface $fileSystem,
    Client $http_client,
    ConfigFactoryInterface $config_factory
  ) {
    $this->fileSystem = $fileSystem;
    $this->httpClient = $http_client;
    $this->pythonConfig = $config_factory->get('dropai_python.settings');
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
      $container->get('config.factory'),
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

  /**
   * Gets document content based on mimetype.
   */
  private function getDocumentContent($path) {
    $basePythonURL = $this->pythonConfig->get('local_python_flask_url');
    $url = "$basePythonURL/document-to-text";
    try {
      $response = $this->httpClient->post($url, [
        'json' => [
          'path' => $path,
        ],
      ],);
      $body = json_decode($response->getBody()->__toString(), TRUE)['data'];
    }
    catch (\Exception $e) {
      $body = $this->t('An error occurred. Make sure the local Python Flask app is running. (from the root, run: lando python web/modules/contrib/dropai/modules/dropai_python/python/app.py) Message: @message', ['@message' => $e->getMessage()]);
    }
    return $body;
  }

}
