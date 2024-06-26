<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\media\MediaInterface;
// use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Base class for DropAI Loader plugins.
 */
abstract class DropaiLoaderBase extends PluginBase implements DropaiLoaderInterface {

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
   * Determines if the given entity is a document media entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity is an document media entity, FALSE otherwise.
   */
  public static function isDocument(EntityInterface $entity): bool {
    if (!self::isMedia($entity)) {
      return FALSE;
    }
    /** @var \Drupal\media\MediaInterface $entity */
    $media_source = self::getMediaSource($entity);
    if ($media_source == 'file') {
      return TRUE;
    }
    return FALSE;
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
    if ($media_source == 'image') {
      return TRUE;
    }
    return FALSE;
  }

}
