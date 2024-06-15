<?php

namespace Drupal\dropai\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\dropai\Service\DocReaderFactory;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DocReaderController extends ControllerBase {

  protected $fileSystem;
  protected $docReaderFactory;

  public function __construct(FileSystemInterface $fileSystem, DocReaderFactory $docReaderFactory) {
    $this->fileSystem = $fileSystem;
    $this->docReaderFactory = $docReaderFactory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('dropai.document_reader.factory')
    );
  }

  public function content(Media $media) {
    $output = '';
    $field_name = 'field_media_document';

    if ($media->bundle() === 'document' && $media->hasField($field_name) && !$media->get($field_name)->isEmpty()) {
      $file = $media->get($field_name)->entity;
      $filePath = $this->fileSystem->realpath($file->getFileUri());

      if (file_exists($filePath)) {
        $docReader = $this->docReaderFactory->create($file);

        if (is_null($docReader)) {
          $output = $this->t('PDF reader type not supported.');
        }
        else {
          $text = $docReader->getText($filePath);
          $output = nl2br($text);
        }
      }
      else {
        $output = $this->t('The file does not exists.');
      }
    }

    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

}
