<?php

namespace Drupal\dropai\Plugin\PdfReader;

use Smalot\PdfParser\Parser;

class PDFParserReader implements PdfReaderInterface {

  public function getText($filePath) {
    $parser = new Parser();
    $pdf = $parser->parseFile($filePath);

    return $pdf->getText();
  }

}
