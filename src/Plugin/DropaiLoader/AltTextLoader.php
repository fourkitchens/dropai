<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dropai\Plugin\DropaiLoaderBase;
use Drupal\media\MediaInterface;

/**
 * Provides a DropAI Loader plugin that fetches an image's alt attribute.
 *
 * @DropaiLoader(
 *   id = "alttext",
 *   label = @Translation("Alt Text")
 * )
 */
class AltTextLoader extends DropaiLoaderBase {

  /**
   * {@inheritdoc}
   */
  public static function applies(EntityInterface $entity) {
    return self::isImage($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(MediaInterface $entity): string {
    $image_field = self::getImageField($entity);
    if ($image_field && isset($image_field['alt'])) {
      return $image_field['alt'];
    }
    return '';
  }

}
