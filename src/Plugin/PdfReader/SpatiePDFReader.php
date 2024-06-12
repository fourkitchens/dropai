<?php

namespace Drupal\dropai\Plugin\PdfReader;

use Spatie\PdfToText\Pdf;

class SpatiePDFReader implements PdfReaderInterface {

  public function getText($filePath) {
    $content = (new Pdf())
      ->setPdf($filePath)
      ->text();

    return $content;
  }

}
