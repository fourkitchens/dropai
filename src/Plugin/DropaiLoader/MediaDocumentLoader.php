<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dropai\Plugin\DropaiLoaderBase;
use Drupal\dropai\Plugin\DropaiLoaderManager;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DropAI Loader plugin that loads documents.
 *
 * @DropaiLoader(
 *   id = "document",
 *   label = @Translation("Document")
 * )
 */
class MediaDocumentLoader extends DropaiLoaderBase implements ContainerFactoryPluginInterface {

  const DOC_FIELD = 'field_media_document';

  /**
   * The DropAI Loader manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiLoaderManager
   */
  public $dropaiLoaderManager;

  /**
   * Constructs a new DocumentLoader object.
   *
   * @param \Drupal\dropai\Plugin\DropaiLoaderManager $dropai_loader_manager
   *   The DropAI Loader manager.
   */
  public function __construct(DropaiLoaderManager $dropai_loader_manager) {
    $this->dropaiLoaderManager = $dropai_loader_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static($container->get('plugin.manager.dropai_loader'));
  }

  /**
   * {@inheritdoc}
   */
  public static function applies(ContentEntityInterface $entity) {
    return self::isDocument($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(ContentEntityInterface $entity): string {
    // Drupal is a flexible CMS so there are many potential ways files can be
    // structured within Media entities. This modules is choosing to follow the
    // out of the box behavior where the Document bundle has a single file.
    if (!self::isDocument($entity) ||!$entity->hasField(self::DOC_FIELD) || $entity->get(self::DOC_FIELD)->isEmpty()){
      return '';
    }
    $file = $entity->get(self::DOC_FIELD)->entity;
    if (!$file instanceof FileInterface) {
      return '';
    }
    $fileType = $file->getMimeType();
    if (empty($fileType)) {
      return '';
    }
    /** $var \Drupal\dropai\Plugin\PluginInspectionInterface $plugin */
    $plugin = $this->dropaiLoaderManager->loadPluginByMimeType($fileType);
    if (!empty($plugin)) {
      return $plugin->load($file);
    }
    return '';
  }

}
