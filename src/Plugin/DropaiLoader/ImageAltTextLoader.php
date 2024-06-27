<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\dropai\Plugin\DropaiLoaderBase;

/**
 * Provides a DropAI Loader plugin that fetches an image's alt attribute.
 *
 * @DropaiLoader(
 *   id = "alttext",
 *   label = @Translation("Alt Text")
 * )
 */
class ImageAltTextLoader extends DropaiLoaderBase {

  const IMAGE_FIELD = 'field_media_image';

  /**
   * {@inheritdoc}
   */
  public static function applies(ContentEntityInterface $entity): bool {
    return self::isImage($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(ContentEntityInterface $entity): string {
    if (!self::isImage($entity) || !$entity->hasField(self::IMAGE_FIELD) || $entity->get(self::IMAGE_FIELD)->isEmpty()){
      return '';
    }
    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $image */
    $image = $entity->get(self::IMAGE_FIELD)->first();
    return $image->get('alt')->getString();
  }

}
