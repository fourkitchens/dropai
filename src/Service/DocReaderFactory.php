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

    if (str_contains($fileType, 'word')) {
      $reader = 'PhpWordReader';
    }
    elseif (str_contains($fileType, 'pdf')) {
      $config = $this->configFactory->get('dropai.pdf_reader.settings');
      $reader = $config->get('pdf_library') ?: 'PDFParserReader';
    }
    else {
      return NULL;
    }

    $file = __DIR__ . '/../Plugin/PdfReader/' . $reader . '.php';
    if (file_exists($file)) {
      $className = 'Drupal\\dropai\\Plugin\\PdfReader\\' . $reader;
      return new $className();
    }
    else {
      return NULL;
    }
  }

}
