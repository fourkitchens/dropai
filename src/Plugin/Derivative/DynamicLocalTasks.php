<?php

namespace Drupal\dropai\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * Constructs a new DynamicLocalTasks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The translation manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TranslationManager $string_translation
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->translationManager = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Skip entity if it does not have the AI Inspector link template.
      if (!$entity_type->hasLinkTemplate('dropai-inspect')) {
        continue;
      }
      $has_canonical_path = $entity_type->hasLinkTemplate('canonical');
      $this->derivatives["$entity_type_id.inspect_tab"] = [
        'route_name' => "entity.$entity_type_id.inspect",
        'title' => $this->translationManager->translate('AI Inspect'),
        'base_route' => "entity.$entity_type_id." . ($has_canonical_path ? "canonical" : "edit_form"),
        'weight' => 100,
      ];
    }
    return $this->derivatives;
  }

}
