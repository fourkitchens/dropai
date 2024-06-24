<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\media\MediaInterface;

/**
 * Base class for DropAI Loader plugins.
 */
abstract class DropaiLoaderBase extends PluginBase implements DropaiLoaderInterface {

  const IMAGE_FIELD = 'field_media_image';

  /**
   * Checks if the entity is a media entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity is a media entity, FALSE otherwise.
   */
  public static function isMedia(EntityInterface $entity): bool {
    return $entity instanceof MediaInterface;
  }

  /**
   * Gets the media source of the media entity.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   The media entity.
   *
   * @return string|null
   *   The media source, or NULL if not available.
   */
  public static function getMediaSource(MediaInterface $entity) {
    return $entity->bundle->entity->get('source');
  }

  /**
   * Gets the image field from the media entity.
   *
   * @todo: Allow additional image media types beyond the default bundle.
   *
   * @param \Drupal\media\MediaInterface $entity
   *   The media entity.
   *
   * @return array|null
   *   The image field if available, NULL otherwise.
   */
  public static function getImageField(MediaInterface $entity): ?array {
    if ($entity->hasField(self::IMAGE_FIELD) && !$entity->get(self::IMAGE_FIELD)->isEmpty()) {
      return $entity->get(self::IMAGE_FIELD)->first()->getValue();
    }
    return NULL;
  }

  /**
   * Determines if the given entity is an image media entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity is an image media entity, FALSE otherwise.
   */
  public static function isImage(EntityInterface $entity): bool {
    if (!self::isMedia($entity)) {
      return FALSE;
    }
    /** @var \Drupal\media\MediaInterface $entity */
    $media_source = self::getMediaSource($entity);
    if ($media_source !== 'image') {
      return FALSE;
    }
    return !empty(self::getImageField($entity));
  }

}
