<?php

namespace Drupal\dropai\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dropai\Service\PdfReaderFactory;
use Drupal\Core\File\FileUrlGeneratorInterface;

class PdfReaderController extends ControllerBase {

  protected $configFactory;
  protected $pdfReaderFactory;
  protected $fileUrlGenerator;

  public function __construct(ConfigFactoryInterface $configFactory, PdfReaderFactory $pdfReaderFactory, FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->configFactory = $configFactory;
    $this->pdfReaderFactory = $pdfReaderFactory;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('dropai.pdf_reader.factory'),
      $container->get('file_url_generator')
    );
  }

  public function content(Media $media) {
    $output = '';
    $field_name = 'field_media_document';

    if ($media->bundle() === 'document' && $media->hasField($field_name) && !$media->get($field_name)->isEmpty()) {
      $file = $media->get($field_name)->entity;
      $filePath = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());

      //$filePath = $media->get($field_name)->entity->getFileUri();
      $config = $this->configFactory->get('dropai.pdf_reader.settings');
      $readerType = $config->get('pdf_library') ?: 'pdfparser';
      $pdfReader = $this->pdfReaderFactory->create($readerType);

      if (is_null($pdfReader)) {
        $output = $this->t('PDF reader type not supported.');
      }
      else {
        $text = $pdfReader->getText($filePath);
        $output = nl2br($text);
      }
    }

    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

}
