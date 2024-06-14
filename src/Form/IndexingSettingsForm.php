<?php

namespace Drupal\dropai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an entity Inspect form.
 */
class IndexingSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DropaiConfigForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dropai.indexing_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropai_indexing_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $config = $this->config('dropai.indexing_settings');

    // Get all content entity types.
    $content_entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->entityClassImplements('Drupal\Core\Entity\ContentEntityInterface')) {
        $content_entity_types[$entity_type_id] = $entity_type->getLabel();
      }
    }

    $form['content_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Entities'),
      '#description' => $this->t('Select the content entity types to be processed by DropAI.'),
      '#options' => $content_entity_types,
      '#default_value' => array_keys($config->get('content_entities')) ?: [],
      '#ajax' => [
        'callback' => '::updateBundleSettings',
        'wrapper' => 'bundle-settings-wrapper',
      ],
    ];

    $form['bundle_settings'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundle-settings-wrapper'],
    ];

    $selected_entities = array_filter($form_state->getValue('content_entities', array_keys($config->get('content_entities')) ?: []));
    foreach ($selected_entities as $entity_type_id) {
      // Skip entities that do not have bundles.
      if (!$this->hasBundles($entity_type_id)) {
        continue;
      }
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id);

      $bundle_options = [];
      foreach ($bundles as $bundle_id => $bundle) {
        $bundle_options[$bundle_id] = $bundle['label'];
      }

      $form['bundle_settings'][$entity_type_id] = [
        '#type' => 'fieldset',
        '#title' => $content_entity_types[$entity_type_id] . ' ' . $this->t('Bundle Settings'),
      ];

      $form['bundle_settings'][$entity_type_id]['index_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Which bundles should be indexed?'),
        '#options' => [
          'include' => $this->t('Only those selected'),
          'exclude' => $this->t('All except those selected'),
        ],
        '#default_value' => $config->get('content_entities')[$entity_type_id]['index_type'] ?? 'include',
      ];

      $form['bundle_settings'][$entity_type_id]['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Bundles'),
        '#options' => $bundle_options,
        '#default_value' => $config->get('content_entities')[$entity_type_id]['bundles'] ?? [],
      ];

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback to update bundle settings.
   */
  public function updateBundleSettings(array &$form, FormStateInterface $form_state) {
    return $form['bundle_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('dropai.indexing_settings');
    $content_entities = array_filter($form_state->getValue('content_entities'));
    $entities_settings = [];
    foreach ($content_entities as $entity_type_id) {
      $entities_settings[$entity_type_id] = [
        'index_type' => $form_state->getValue(['bundle_settings', $entity_type_id, 'index_type'], 'any'),
        'bundles' => $form_state->getValue(['bundle_settings', $entity_type_id, 'bundles']),
      ];
    }

    $config->set('content_entities', $entities_settings)->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns the definition of this datasource's entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type definition.
   */
  protected function getEntityTypeDefinition($entity_type_id) {
    return $this->entityTypeManager->getDefinition($entity_type_id);
  }

  /**
   * Determines whether the entity type supports bundles.
   *
   * @return bool
   *   TRUE if the entity type supports bundles, FALSE otherwise.
   */
  protected function hasBundles($entity_type_id) {
    return $this->getEntityTypeDefinition($entity_type_id)->hasKey('bundle');
  }

}
