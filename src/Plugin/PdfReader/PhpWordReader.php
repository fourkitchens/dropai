<?php

namespace Drupal\dropai\Plugin\PdfReader;

use PhpOffice\PhpWord\IOFactory;

class PhpWordReader implements DocReaderInterface {

  public function getText($filePath) {
    $content = '';

    try {
      $phpWord = IOFactory::load($filePath);

      foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
          if (method_exists($element, 'getText')) {
            $content .= $element->getText() . "\n";
          }
        }
      }
    } catch (Exception $e) { }

    return $content;
  }

}
