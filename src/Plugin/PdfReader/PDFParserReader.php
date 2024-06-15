<?php

namespace Drupal\dropai\Plugin\PdfReader;

use Smalot\PdfParser\Parser;

class PDFParserReader implements DocReaderInterface {

  public function getText($filePath) {
    $content = '';
    $parser = new Parser();

    try {
      $pdfDoc = $parser->parseFile($filePath);
      $content = $pdfDoc->getText();
    } catch (Exception $e) { }

    return $content;
  }

}
