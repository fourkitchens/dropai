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

  const IMAGE_FIELD = 'field_media_image';

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
    if (!$entity->hasField(self::IMAGE_FIELD) || $entity->get(self::IMAGE_FIELD)->isEmpty()){
      return '';
    }
    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $image */
    $image = $entity->get(self::IMAGE_FIELD)->first();
    return $image->get('alt')->getString();
  }

}
