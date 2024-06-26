<?php

namespace Drupal\dropai_openai\Plugin\DropaiLoader;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dropai\Plugin\DropaiLoaderBase;
use Drupal\media\MediaInterface;

/**
 * Provides a DropAI Loader plugin that returns a text description of an image.
 *
 * @DropaiLoader(
 *   id = "openaivision",
 *   label = @Translation("OpenAI Vision")
 * )
 */
class OpenAiVisionLoader extends DropaiLoaderBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new EntityRenderLoader object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
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
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

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
    $openai = \Drupal::service('dropai_openai.connector');
    return $openai->getTextFromImage($entity);
  }

}
