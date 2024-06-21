<?php

namespace Drupal\dropai\Plugin\DocReader;

use PhpOffice\PhpWord\IOFactory;

class PhpWord97Reader implements DocReaderInterface {

  public function getText($filePath) {
    $content = '';

    try {
      $reader = IOFactory::createReader('MsDoc');
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
