<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dropai\Plugin\DropaiLoaderBase;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;

/**
 * Provides a DropAI Loader plugin that loads documents.
 *
 * @DropaiLoader(
 *   id = "document",
 *   label = @Translation("Document")
 * )
 */
class DocumentLoader extends DropaiLoaderBase {

  const DOC_FIELD = 'field_media_document';

  /**
   * {@inheritdoc}
   */
  public static function applies(EntityInterface $entity) {
    return self::isDocument($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(MediaInterface $entity): string {
    if (!$entity->hasField(self::DOC_FIELD) || $entity->get(self::DOC_FIELD)->isEmpty()){
      return '';
    }
    $file = $entity->get(self::DOC_FIELD)->entity;
    if (!$file instanceof FileInterface) {
      return '';
    }
    return $file->getMimeType();
  }

}
