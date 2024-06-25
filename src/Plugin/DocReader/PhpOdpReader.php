<?php

namespace Drupal\dropai\Plugin\DocReader;

use PhpOffice\PhpPresentation\IOFactory;

class PhpOdpReader implements DocReaderInterface {

  public function getText($filePath) {
    $content = '';

    try {
      $reader = IOFactory::createReader('ODPresentation');
      $document = $reader->load($filePath);

      foreach ($document->getAllSlides() as $slide) {
        foreach ($slide->getShapeCollection() as $shape) {
          if (method_exists($shape, 'getText')) {
            $content .= $shape->getText() . "\n";
          }
        }
      }
    } catch (Exception $e) { }

    return $content;
  }

}
