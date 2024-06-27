<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\dropai\Plugin\DropaiLoader\FileLoader;
use PhpOffice\PhpWord\IOFactory;

/**
 * Provides a DropAI Loader plugin that loads Word documents.
 *
 * @DropaiLoader(
 *   id = "word",
 *   label = @Translation("Word"),
 *   mime_type = "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
 * )
 */
class FileWordLoader extends FileLoader {

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
      $reader = IOFactory::createReader('Word2007');
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
