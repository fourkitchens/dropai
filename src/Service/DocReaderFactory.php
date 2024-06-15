<?php

namespace Drupal\dropai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

class DocReaderFactory {

  protected $configFactory;

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

    if (str_contains($fileType, 'word')) {
      $reader = $config->get('word_library') ?: 'PhpWordReader';
    }
    elseif (str_contains($fileType, 'opendocument')) {
      $reader = $config->get('odt_library') ?: 'PhpOdtReader';
    }
    elseif (str_contains($fileType, 'pdf')) {
      $reader = $config->get('pdf_library') ?: 'PDFParserReader';
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
