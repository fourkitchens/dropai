<?php

namespace Drupal\dropai\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dropai\Service\PdfReaderFactory;

class PdfReaderController extends ControllerBase {

  protected $configFactory;
  protected $pdfReaderFactory;

  public function __construct(ConfigFactoryInterface $configFactory, PdfReaderFactory $pdfReaderFactory) {
    $this->configFactory = $configFactory;
    $this->pdfReaderFactory = $pdfReaderFactory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('dropai.pdf_reader.factory')
    );
  }

  public function content(Media $media) {
    $output = '';
    $field_name = 'field_media_document';

    if ($media->bundle() === 'document' && $media->hasField($field_name) && !$media->get($field_name)->isEmpty()) {
      $filePath = $media->get($field_name)->entity->getFileUri();
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
