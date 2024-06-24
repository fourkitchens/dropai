<?php

namespace Drupal\dropai\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\dropai\Plugin\DropaiEmbeddingManager;
use Drupal\dropai\Plugin\DropaiLoaderManager;
use Drupal\dropai\Plugin\DropaiPreprocessorManager;
use Drupal\dropai\Plugin\DropaiSplitterManager;
use Drupal\dropai\Plugin\DropaiTokenizerManager;

/**
 * Implements an entity Inspect form.
 */
class EntityInspectForm extends FormBase {

  /**
   * The DropAI Embedding plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiEmbeddingManager
   */
  protected $dropaiEmbeddingManager;

  /**
   * The DropAI Loader plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiLoaderManager
   */
  protected $dropaiLoaderManager;

  /**
   * The DropAI Preprocessor plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiPreprocessorManager
   */
  protected $dropaiPreprocessorManager;

  /**
   * The DropAI Splitter plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiSplitterManager
   */
  protected $dropaiSplitterManager;

  /**
   * The DropAI Tokenizer plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiTokenizerManager
   */
  protected $dropaiTokenizerManager;

  /**
   * The entity ready to clone.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new form.
   *
   * @param \Drupal\dropai\Plugin\DropaiEmbeddingManager $dropai_embedding_manager
   *   The DropAI Embedding plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiLoaderManager $dropai_loader_manager
   *   The DropAI Loader plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiPreprocessorManager $dropai_preprocessor_manager
   *   The DropAI Preprocessor plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiSplitterManager $dropai_splitter_manager
   *   The DropAI Splitter plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiTokenizerManager $dropai_tokenizer_manager
   *   The DropAI Tokenizer plugin manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    DropaiEmbeddingManager $dropai_embedding_manager,
    DropaiLoaderManager $dropai_loader_manager,
    DropaiPreprocessorManager $dropai_preprocessor_manager,
    DropaiSplitterManager $dropai_splitter_manager,
    DropaiTokenizerManager $dropai_tokenizer_manager,
    RouteMatchInterface $route_match,
    MessengerInterface $messenger,
  ) {
    $this->dropaiEmbeddingManager = $dropai_embedding_manager;
    $this->dropaiLoaderManager = $dropai_loader_manager;
    $this->dropaiPreprocessorManager = $dropai_preprocessor_manager;
    $this->dropaiSplitterManager = $dropai_splitter_manager;
    $this->dropaiTokenizerManager = $dropai_tokenizer_manager;
    $this->messenger = $messenger;
    $parameter_name = $route_match->getRouteObject()->getOption('_dropai_entity_type_id');
    $this->entity = $route_match->getParameter($parameter_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.dropai_embedding'),
      $container->get('plugin.manager.dropai_loader'),
      $container->get('plugin.manager.dropai_preprocessor'),
      $container->get('plugin.manager.dropai_splitter'),
      $container->get('plugin.manager.dropai_tokenizer'),
      $container->get('current_route_match'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_inspect_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'dropai/inspector-form';

    /*
     * Step 1: Load the source content.
     * TBD.
     */
    $form['loader'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'loader-wrapper',
        'class' => 'section-grid',
      ],
    ];
    $form['loader']['loader_plugin'] = [
      '#type' => 'select',
      '#title' => 'Loader',
      '#default_value' => 'entityrender',
      '#options' => $this->dropaiLoaderManager->getPluginOptions($this->entity),
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $loader_id = $form_state->getValue('loader_plugin', 'entityrender');
    $loader = $this->dropaiLoaderManager->createInstance($loader_id);
    $source = $loader->load($this->entity);
    $form['loader']['source'] = [
      '#theme' => 'code_block',
      '#language' => 'markdown',
      '#code' => $source,
    ];

    /*
     * Step 2: Preprocess the content.
     * The preprocessor converts the source content into a format this is more
     * friendly to the AI ingestion tools. This may include stripping unwanted
     * markup, trimming whitespace, and converting complex content to readible
     * equivalents.
     */
    $form['preprocessor'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'preprocessor-wrapper',
        'class' => 'section-grid',
      ],
    ];
    $form['preprocessor']['preprocessor_plugin'] = [
      '#type' => 'select',
      '#title' => 'Preprocessor',
      '#default_value' => 'plaintext',
      '#options' => $this->dropaiPreprocessorManager->getPluginOptions(),
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $preprocessor = $form_state->getValue('preprocessor_plugin', 'plaintext');
    $plugin = $this->dropaiPreprocessorManager->createInstance($preprocessor);
    $processed = $plugin->preprocess($source);
    $form['preprocessor']['source_processed'] = [
      '#theme' => 'code_block',
      '#language' => 'markdown',
      '#code' => $processed,
    ];

    /*
     * Step 3: Tokenize the content.
     * The tokenizer tool calculates the content size in tokens. This is
     * important as AI models have different limits on total token size. This
     * can also inform content owners of how their content impacts API costs.
     */
    $form['tokenizer'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'tokenizer-wrapper',
        'class' => 'section-grid',
      ],
    ];
    $form['tokenizer']['controls'] = [
      '#type' => 'container',
    ];
    $form['tokenizer']['controls']['tokenizer_plugin'] = [
      '#type' => 'select',
      '#title' => 'Tokenizer',
      '#default_value' => 'tiktoken',
      '#options' => $this->dropaiTokenizerManager->getPluginOptions(),
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $tokenizer_id = $form_state->getValue('tokenizer_plugin', 'tiktoken');
    $tokenizer = $this->dropaiTokenizerManager->createInstance($tokenizer_id);
    $form['tokenizer']['controls']['tokenizer_model'] = [
      '#type' => 'select',
      '#title' => 'Model',
      '#default_value' => 'gpt-4o',
      '#options' => $tokenizer->getModels(),
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $model = $form_state->getValue('tokenizer_model', 'gpt-4o');
    $tokens = $tokenizer->tokenize($processed, $model);
    $form['tokenizer']['controls']['char_count'] = [
      '#type' => 'textfield',
      '#title' => 'Characters',
      '#disabled' => TRUE,
      '#size' => 23,
      '#value' => strlen($processed),
    ];
    $form['tokenizer']['controls']['token_count'] = [
      '#type' => 'textfield',
      '#title' => 'Tokens',
      '#disabled' => TRUE,
      '#size' => 23,
      '#value' => count($tokens),
    ];
    $form['tokenizer']['output'] = [
      '#type' => 'container',
    ];
    $form['tokenizer']['output'] ['tokens'] = [
      '#theme' => 'code_block',
      '#language' => 'php',
      '#code' => json_encode($tokens),
    ];

    /*
     * Step 4: Splitting the content.
     * A single piece of content can be broken into smaller chunks to influence
     * how the page is surfaced in vector search results.
     */
    $form['splitter'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'splitter-wrapper',
        'class' => 'section-grid',
      ],
    ];
    $form['splitter']['controls'] = [
      '#type' => 'container',
    ];
    $form['splitter']['controls']['splitter_plugin'] = [
      '#type' => 'select',
      '#title' => 'Splitter',
      '#default_value' => 'fixed',
      '#options' => $this->dropaiSplitterManager->getPluginOptions(),
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $form['splitter']['controls']['split_max_size'] = [
      '#type' => 'number',
      '#title' => 'Split size (characters)',
      '#default_value' => 1000,
      '#min' => 1,
      '#step' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="splitter_plugin"]' => ['value' => 'none'],
        ],
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $form['splitter']['controls']['split_overlap'] = [
      '#type' => 'number',
      '#title' => 'Overlap (%)',
      '#default_value' => 20,
      '#min' => 1,
      '#max' => 100,
      '#step' => 1,
      '#states' => [
        'visible' => [
          ':input[name="splitter_plugin"]' => ['value' => 'window'],
        ],
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $splitter_id = $form_state->getValue('splitter_plugin', 'fixed');
    $splitMaxSize = $form_state->getValue('split_max_size', '1000');
    $splitOverlap = $form_state->getValue('split_overlap', '1000') * .01;
    $splitter = $this->dropaiSplitterManager->createInstance($splitter_id);
    $chunks = $splitter->split($processed, maxSize: $splitMaxSize, overlap: $splitOverlap);
    $form['splitter']['controls']['count'] = [
      '#type' => 'textfield',
      '#title' => 'Chunk count',
      '#disabled' => TRUE,
      '#size' => 23,
      '#value' => count($chunks),
    ];
    $form['splitter']['preview'] = [
      '#theme' => 'array_block',
      '#items' => $chunks,
    ];

    /*
     * Step 5: Generating embeddings.
     * TBD. Considering using OpenAI API and Hugging Face Transformers Python.
     */
    $form['embedding'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'embedding-wrapper',
        'class' => 'section-grid',
      ],
    ];
    $form['embedding']['controls'] = [
      '#type' => 'container',
    ];
    $form['embedding']['controls']['embedding_plugin'] = [
      '#type' => 'select',
      '#title' => 'Embedding',
      '#default_value' => 'none',
      '#options' => $this->dropaiEmbeddingManager->getPluginOptions(),
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $embedding_id = $form_state->getValue('embedding_plugin', 'none');
    $embedding = $this->dropaiEmbeddingManager->createInstance($embedding_id);
    $form['embedding']['controls']['embedding_model'] = [
      '#type' => 'select',
      '#title' => 'Model',
      '#options' => $embedding->getModels(),
      "#empty_option"=>t('- None -'),
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $model = $form_state->getValue('embedding_model');
    $embeddings = !empty($model) ? $embedding->getEmbeddings($chunks, $model) : [];
    $embeddings_preview = array_map(function($embedding) {
      return json_encode($embedding);
    }, $embeddings);
    $form['embedding']['preview'] = [
      '#theme' => 'array_block',
      '#items' => $embeddings_preview,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * AJAX callback to update multiple parts of the form.
   */
  public function updateFormParts(array &$form, FormStateInterface $form_state) {
    // Rebuild wrappers with content that may conditionally change.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#loader-wrapper', $form['loader']));
    $response->addCommand(new ReplaceCommand('#preprocessor-wrapper', $form['preprocessor']));
    $response->addCommand(new ReplaceCommand('#tokenizer-wrapper', $form['tokenizer']));
    $response->addCommand(new ReplaceCommand('#splitter-wrapper', $form['splitter']));
    $response->addCommand(new ReplaceCommand('#embedding-wrapper', $form['embedding']));
    return $response;
  }

}
