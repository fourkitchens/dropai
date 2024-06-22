<?php

namespace Drupal\dropai\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\dropai\Plugin\DropaiEmbeddingManager;
use Drupal\dropai\Plugin\DropaiPreprocessorManager;
use Drupal\dropai\Plugin\DropaiSplitterManager;
use Drupal\dropai\Plugin\DropaiTokenizerManager;
use Drupal\dropai_openai\OpenaiEntityToTextInterface;

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
   * The DropAI OpenAI Image To Text service.
   *
   * @var \Drupal\dropai_openai\OpenaiEntityToTextInterface
   */
  protected $entityToText;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity ready to clone.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity type définition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityTypeDefinition;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new form.
   *
   * @param \Drupal\dropai\Plugin\DropaiEmbeddingManager $dropai_embedding_manager
   *   The DropAI Embedding plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiPreprocessorManager $dropai_preprocessor_manager
   *   The DropAI Preprocessor plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiSplitterManager $dropai_splitter_manager
   *   The DropAI Splitter plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiTokenizerManager $dropai_tokenizer_manager
   *   The DropAI Tokenizer plugin manager.
   * @param \Drupal\dropai_openai\OpenaiEntityToTextInterface $entity_to_text
   *   The DropAI OpenAI Entity To Text service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    DropaiEmbeddingManager $dropai_embedding_manager,
    DropaiPreprocessorManager $dropai_preprocessor_manager,
    DropaiSplitterManager $dropai_splitter_manager,
    DropaiTokenizerManager $dropai_tokenizer_manager,
    OpenaiEntityToTextInterface $entity_to_text,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    MessengerInterface $messenger,
    RendererInterface $renderer
  ) {
    $this->dropaiEmbeddingManager = $dropai_embedding_manager;
    $this->dropaiPreprocessorManager = $dropai_preprocessor_manager;
    $this->dropaiSplitterManager = $dropai_splitter_manager;
    $this->dropaiTokenizerManager = $dropai_tokenizer_manager;
    $this->entityToText = $entity_to_text;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $parameter_name = $route_match->getRouteObject()->getOption('_dropai_entity_type_id');
    $this->entity = $route_match->getParameter($parameter_name);
    $this->entityTypeDefinition = $entity_type_manager->getDefinition($this->entity->getEntityTypeId());
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.dropai_embedding'),
      $container->get('plugin.manager.dropai_preprocessor'),
      $container->get('plugin.manager.dropai_splitter'),
      $container->get('plugin.manager.dropai_tokenizer'),
      $container->get('dropai_openai.entity_to_text'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('messenger'),
      $container->get('renderer'),
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
     * Step 1: Fetch the source content.
     * The fetch method loads the source content as text. At the moment, this
     * only supports loading entities based on their rendered markup.
     */
    $source = $this->fetchContent($this->entity);
    $form['source'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'source-wrapper',
        'class' => 'section-grid',
      ],
    ];
    $form['source']['source_plugin'] = [
      '#type' => 'select',
      '#title' => 'Source Plugin',
      '#default_value' => 'entity',
      '#options' => [
        'entity' => 'Entity',
      ],
    ];
    $form['source']['source_raw'] = [
      '#theme' => 'code_block',
      '#language' => 'xml',
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
    $model = $form_state->getValue('embedding_model', '');
    $embeddings = $embedding->getEmbeddings($chunks, $model);
    // $form['embedding']['preview'] = [
    //   '#theme' => 'code_block',
    //   '#language' => 'php',
    //   '#code' => json_encode($embeddings),
    // ];
    $embeddings = array_map(function($chunk) {
      return json_encode($chunk);
    }, $embeddings);
    $form['embedding']['preview'] = [
      '#theme' => 'array_block',
      '#items' => $embeddings,
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
    $response->addCommand(new ReplaceCommand('#source-wrapper', $form['source']));
    $response->addCommand(new ReplaceCommand('#preprocessor-wrapper', $form['preprocessor']));
    $response->addCommand(new ReplaceCommand('#tokenizer-wrapper', $form['tokenizer']));
    $response->addCommand(new ReplaceCommand('#splitter-wrapper', $form['splitter']));
    $response->addCommand(new ReplaceCommand('#embedding-wrapper', $form['embedding']));
    return $response;
  }

  public function fetchContent(EntityInterface $entity) {
    if($entity->getEntityTypeId() == 'media') {
      if ($entity->bundle() == 'image' || $entity->bundle() == 'audio') {
        return $this->entityToText->getEntityAsText($entity);
      }
      elseif ($entity->bundle() == 'document') {
        // Docloaders here.
      }
      else {
        return '';
      }
    }
    else {
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $renderArray = $view_builder->view($entity, 'default');
      return $this->renderer->render($renderArray)->__toString();
    }
  }

}
