<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\dropai\Plugin\DropaiLoader\FileLoader;

/**
 * Provides a DropAI Loader plugin that loads PDF documents.
 *
 * @DropaiLoader(
 *   id = "txt",
 *   label = @Translation("TXT"),
 *   mime_type = "text/plain"
 * )
 */
class FileTxtLoader extends FileLoader {

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
    if (!self::isFile($entity)) {
      return '';
    }
    try {
      $content = file_get_contents($this->getFilePath($entity));
    }
    catch (\Exception $e) {
      // @todo: catch errors.
    }
    return '';
  }

}
