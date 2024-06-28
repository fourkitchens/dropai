<?php

namespace Drupal\dropai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\dropai\Plugin\DropaiStorageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an indexing settings form for DropAI.
 */
class IndexingSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\dropai\Plugin\DropaiStorageManager
   */
  protected DropaiStorageManager $dropaiStorageManager;

  /**
   * Constructs a new IndexingSettingsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    DropaiStorageManager $dropaiStorageManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->dropaiStorageManager = $dropaiStorageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.dropai_storage')
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

    // Select the content bundles that should be indexed.
    $form['source'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Content Entities'),
    ];
    $form['source']['instructions'] = [
      '#markup' => $this->t('The DropAI module allows you to generate embeddings for your Drupal content to be used in a Retrieval-Augmented Generation (RAG) architecture. This interface enables you to select which content entities to include in the vector database. If an entity has bundles, you can also configure rules to include or exclude specific types.'),
    ];
    $content_entity_types = $this->getContentEntityTypes();
    $form['source']['entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Entities'),
      '#options' => $content_entity_types,
      '#default_value' => array_keys($config->get('entities') ?? []),
      '#ajax' => [
        'callback' => '::updateBundleSettings',
        'wrapper' => 'bundle-settings-wrapper',
      ],
    ];

    // Set rules to include or exclude specific entity bundles.
    $form['bundle_settings'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundle-settings-wrapper'],
    ];
    $selected = array_filter($form_state->getValue(['source', 'entities'], array_keys($config->get('entities') ?? [])));
    foreach ($selected as $entity_type_id) {
      // Skip entities that do not have bundles.
      if (!$this->hasBundles($entity_type_id)) {
        continue;
      }
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      $bundle_options = [];
      foreach ($bundles as $bundle_id => $bundle) {
        $bundle_options[$bundle_id] = $bundle['label'];
      }
      $form['bundle_settings'][$entity_type_id] = [
        '#type' => 'details',
        '#closed' => TRUE,
        '#title' => $content_entity_types[$entity_type_id] . ' ' . $this->t('Bundle Settings'),
      ];
      $form['bundle_settings'][$entity_type_id]['index_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Which bundles should be indexed?'),
        '#options' => [
          'include' => $this->t('Only those selected'),
          'exclude' => $this->t('All except those selected'),
        ],
        '#default_value' => $config->get('entities')[$entity_type_id]['index_type'] ?? 'exclude',
      ];
      $form['bundle_settings'][$entity_type_id]['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Bundles'),
        '#options' => $bundle_options,
        '#default_value' => $config->get('entities')[$entity_type_id]['bundles'] ?? [],
      ];
    }

    // Get the storage plugin options.
    $storage_options = $this->dropaiStorageManager->getPluginOptions();
    $form['storage_settings'] = [
      '#type' => 'container',
    ];
    $form['storage_settings']['storage_plugin'] = [
      '#type' => 'select',
      '#options' => $storage_options,
      '#default_value' => $config->get('storage_plugin'),
    ];

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
    $entities = array_filter($form_state->getValue(['source', 'entities']));
    $entities_settings = [];
    foreach ($entities as $entity_type_id) {
      $entities_settings[$entity_type_id] = [
        'index_type' => $form_state->getValue(['bundle_settings', $entity_type_id, 'index_type'], 'exclude'),
        'bundles' => array_filter($form_state->getValue(['bundle_settings', $entity_type_id, 'bundles']) ?? [])
      ];
    }
    $config->set('storage_plugin', $form_state->getValue(['storage_settings', 'storage_plugin'], 'none'))->save();
    $config->set('entities', $entities_settings)->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Retrieves the list of content entity types.
   *
   * @return array
   *   An array of content entity type labels keyed by entity type ID.
   */
  protected function getContentEntityTypes() {
    $content_entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->entityClassImplements('Drupal\Core\Entity\\ContentEntityInterface')) {
        $content_entity_types[$entity_type_id] = $entity_type->getLabel();
      }
    }
    return $content_entity_types;
  }

  /**
   * Determines whether the entity type supports bundles.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return bool
   *   TRUE if the entity type supports bundles, FALSE otherwise.
   */
  protected function hasBundles($entity_type_id) {
    return $this->entityTypeManager->getDefinition($entity_type_id)->hasKey('bundle');
  }

}
