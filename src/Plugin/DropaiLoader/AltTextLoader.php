<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dropai\Plugin\DropaiLoaderBase;
use Drupal\media\Entity\Media;

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

  public static function applies(EntityInterface $entity) {
    if (!$entity instanceof Media) {
      return FALSE;
    }
    $media_source = $entity->bundle->entity->get('source');
    if ($media_source != 'image') {
      return FALSE;
    };
    if ($entity->hasField(self::IMAGE_FIELD)) {
      return TRUE;
    }
    return FALSE;
  }

  public function load(EntityInterface $entity) {
    /** @var \Drupal\media\Entity\Media $entity */
    $image = $entity->get(self::IMAGE_FIELD);
    if ($image->isEmpty()) {
      return '';
    }
    return $image->first()->getValue()['alt'] ?? '';
  }

}
