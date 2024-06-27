<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\dropai\Plugin\DropaiLoader\FileLoader;
use PhpOffice\PhpWord\IOFactory;

/**
 * Provides a DropAI Loader plugin that loads Word97 documents.
 *
 * @DropaiLoader(
 *   id = "word97",
 *   label = @Translation("Word97"),
 *   mime_type = "application/msword"
 * )
 */
class FileWord97Loader extends FileLoader {

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
      $reader = IOFactory::createReader('MsDoc');
      $document = $reader->load($this->getFilePath($entity));
      foreach ($document->getSections() as $section) {
        foreach ($section->getElements() as $element) {
          if (method_exists($element, 'getText')) {
            $content .= $element->getText() . "\n";
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
