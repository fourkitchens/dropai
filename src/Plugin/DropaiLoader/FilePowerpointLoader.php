<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\dropai\Plugin\DropaiLoader\FileLoader;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\RichText;

/**
 * Provides a DropAI Loader plugin that loads PowerPoint documents.
 *
 * @DropaiLoader(
 *   id = "powerpoint",
 *   label = @Translation("PowerPoint"),
 *   mime_type = "application/vnd.openxmlformats-officedocument.presentationml.presentation"
 * )
 */
class FilePowerpointLoader extends FileLoader {


  /**
   * {@inheritdoc}
   */
  public static function applies(ContentEntityInterface $entity) {
    return self::isFile($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(ContentEntityInterface $entity): string {
    $content = '';
    if (!self::isFile($entity)) {
      return '';
    }
    try {
      $reader = IOFactory::createReader('PowerPoint2007');
      $document = $reader->load($this->getFilePath($entity));
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
    }
    catch (\Exception $e) {
      // @todo: catch errors.
    }
    return $content;
  }

}
