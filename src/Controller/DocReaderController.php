<?php

namespace Drupal\dropai\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\dropai\Service\DocReaderFactory;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a controller for the Document Reader pages.
 */
class DocReaderController extends ControllerBase {

  /**
   * The FileSystem Interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The DocReader factory.
   *
   * @var \Drupal\dropai\Service\DocReaderFactory
   */
  protected $docReaderFactory;

  /**
   * Constructs a new DocReaderFactory object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The FileSystem Interface.
   * @param \Drupal\dropai\Service\DocReaderFactory $docReaderFactory
   *   The DocReader factory.
   */
  public function __construct(FileSystemInterface $fileSystem, DocReaderFactory $docReaderFactory) {
    $this->fileSystem = $fileSystem;
    $this->docReaderFactory = $docReaderFactory;
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
      $container->get('dropai.document_reader.factory')
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
        $docReader = $this->docReaderFactory->create($file);

        if (is_null($docReader)) {
          $content = $this->t('Document format not supported.');
        }
        else {
          $content = nl2br($docReader->getText($filePath));
        }
      }
      else {
        $content = $this->t('The file does not exists.');
      }
    }

    return [
      '#title' => $this->t('Document Content: @title', ['@title' => $title]),
      'content' => [
        '#type' => 'markup',
        '#markup' => $content,
      ],
    ];
  }

}
