<?php

namespace Drupal\dropai\Plugin\DocReader;

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\RichText;

class PhpPresentationReader implements DocReaderInterface {

  public function getText($filePath) {
    $content = '';

    try {
      $reader = IOFactory::createReader('PowerPoint2007');
      $document = $reader->load($filePath);

      foreach ($document->getAllSlides() as $slide) {
        foreach ($slide->getShapeCollection() as $shape) {
          if ($shape instanceof RichText) {
            foreach ($shape->getParagraphs() as $paragraph) {
              foreach ($paragraph->getRichTextElements() as $element) {
                $content .= $element->getText() . "\n";
              }
            }
          }
        }
      }
    } catch (Exception $e) { }

    return $content;
  }

}
