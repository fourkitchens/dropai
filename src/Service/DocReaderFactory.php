<?php

namespace Drupal\dropai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a service to build a factory for the document reader.
 */
class DocReaderFactory {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DocReaderFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Provide a factory class to create a PDF Reader.
   */
  public function create($file) {
    $reader = '';
    $fileType = $file->get('filemime')->value;
    $config = $this->configFactory->get('dropai.document_reader.settings');

    if (str_contains($fileType, 'pdf')) {
      $reader = $config->get('pdf_library') ?: 'PDFParserReader';
    }
    elseif (str_contains($fileType, 'officedocument.wordprocessingml')) {
      $reader = $config->get('word_library') ?: 'PhpWordReader';
    }
    elseif (str_contains($fileType, 'officedocument.presentationml')) {
      $reader = 'PhpPresentationReader';
    }
    elseif (str_contains($fileType, 'opendocument.text')) {
      $reader = 'PhpOdtReader';
    }
    elseif (str_contains($fileType, 'opendocument.presentation')) {
      $reader = 'PhpOdpReader';
    }
    elseif (str_contains($fileType, 'msword')) {
      $reader = 'PhpWord97Reader';
    }
    elseif (str_contains($fileType, 'ms-powerpoint')) {
      $reader = 'PhpPresentation97Reader';
    }
    else {
      return NULL;
    }

    $file = __DIR__ . '/../Plugin/DocReader/' . $reader . '.php';
    if (file_exists($file)) {
      $className = 'Drupal\\dropai\\Plugin\\DocReader\\' . $reader;
      return new $className();
    }
    else {
      return NULL;
    }
  }

}
