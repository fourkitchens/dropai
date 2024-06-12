<?php

namespace Drupal\dropai\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

class PdfReaderFactory {

  /**
   * Provide a factory class to create a PDF Reader.
   */
  public function create($type) {
    $file = __DIR__ . '/../Plugin/PdfReader/' . $type . '.php';

    if (file_exists($file)) {
      $className = 'Drupal\\dropai\\Plugin\\PdfReader\\' . $type;
      return new $className();
    }

    return NULL;
  }

}
