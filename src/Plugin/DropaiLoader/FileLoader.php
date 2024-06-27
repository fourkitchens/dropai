<?php

namespace Drupal\dropai\Plugin\DropaiLoader;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dropai\Plugin\DropaiLoaderBase;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a DropAI Loader plugin that loads files.
 *
 * @DropaiLoader(
 *   id = "file",
 *   label = @Translation("File")
 * )
 */
class FileLoader extends DropaiLoaderBase implements ContainerFactoryPluginInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  public $fileSystem;

  /**
   * Constructs a new FileLoader object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
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
    return new static($container->get('file_system'));
  }

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
    // @todo: What should we return as a fallback?
    return '';
  }

  /**
   * Get the realpath for the file associated with this entity.
   *
   * @param FileInterface $entity
   *   A file entity.
   *
   * @return string
   *   The real path for this file.
   */
  public function getFilePath(FileInterface $entity): string {
    return $this->fileSystem->realpath($entity->getFileUri());
  }

}
