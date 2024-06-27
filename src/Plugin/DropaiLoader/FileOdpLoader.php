<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\dropai\Plugin\DropaiLoader\FileLoader;
use PhpOffice\PhpPresentation\IOFactory;

/**
 * Provides a DropAI Loader plugin that loads ODP documents.
 *
 * @DropaiLoader(
 *   id = "odp",
 *   label = @Translation("Open Document Text"),
 *   mime_type = "application/vnd.oasis.opendocument.presentation"
 * )
 */
class FileOdpLoader extends FileLoader {

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
      $reader = IOFactory::createReader('ODPresentation');
      $document = $reader->load($this->getFilePath($entity));
      foreach ($document->getAllSlides() as $slide) {
        foreach ($slide->getShapeCollection() as $shape) {
          if (method_exists($shape, 'getText')) {
            /** @var \PhpOffice\PhpPresentation\Shape\AutoShape $shape */
            $content .= $shape->getText() . "\n";
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
