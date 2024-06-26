<?php

namespace Drupal\dropai\Plugin\DocReader;

use PhpOffice\PhpWord\IOFactory;

class PhpWordReader implements DocReaderInterface {

  public function getText($filePath) {
    $content = '';

    try {
      $reader = IOFactory::createReader('Word2007');
      $document = $reader->load($filePath);

      foreach ($document->getSections() as $section) {
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
