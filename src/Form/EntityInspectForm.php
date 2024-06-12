<?php

namespace Drupal\dropai\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\dropai\Plugin\DropaiPreprocessorManager;
use Drupal\dropai\Plugin\DropaiTokenizerManager;

/**
 * Implements an entity Inspect form.
 */
class EntityInspectForm extends FormBase {

  /**
   * The DropAI Preprocessor plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiPreprocessorManager
   */
  protected $dropaiPreprocessorManager;

  /**
   * The DropAI Tokenizer plugin manager.
   *
   * @var \Drupal\dropai\Plugin\DropaiTokenizerManager
   */
  protected $dropaiTokenizerManager;

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new Entity Clone form.
   *
   * @param \Drupal\dropai\Plugin\DropaiPreprocessorManager $dropai_preprocessor_manager
   *   The DropAI Preprocessor plugin manager.
   * @param \Drupal\dropai\Plugin\DropaiTokenizerManager $dropai_tokenizer_manager
   *   The DropAI Tokenizer plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    DropaiPreprocessorManager $dropai_preprocessor_manager,
    DropaiTokenizerManager $dropai_tokenizer_manager,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    MessengerInterface $messenger,
    AccountProxyInterface $currentUser,
    RendererInterface $renderer
  ) {
    $this->dropaiPreprocessorManager = $dropai_preprocessor_manager;
    $this->dropaiTokenizerManager = $dropai_tokenizer_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $parameter_name = $route_match->getRouteObject()->getOption('_dropai_entity_type_id');
    $this->entity = $route_match->getParameter($parameter_name);
    $this->entityTypeDefinition = $entity_type_manager->getDefinition($this->entity->getEntityTypeId());
    $this->currentUser = $currentUser;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.dropai_preprocessor'),
      $container->get('plugin.manager.dropai_tokenizer'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('messenger'),
      $container->get('current_user'),
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
     * Step 4: Chunking the content.
     * A single piece of content can be broken into smaller chunks to influence
     * how the page is surfaced in vector search results.
     */
    $form['chunking'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'chunking-wrapper',
        'class' => 'section-grid',
      ],
    ];
    $form['chunking']['controls'] = [
      '#type' => 'container',
    ];
    $form['chunking']['controls']['chunking_plugin'] = [
      '#type' => 'select',
      '#title' => 'Chunking Strategy',
      '#default_value' => 'fixed',
      '#options' => [
        'none' => 'None',
        'fixed' => 'Fixed (preserve words)',
        'paragraph' => 'Paragraph splitting',
        'window' => 'Sliding window',
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $form['chunking']['controls']['chunk_max_size'] = [
      '#type' => 'number',
      '#title' => 'Chunk max size (character)',
      '#default_value' => 500,
      '#min' => 1,
      '#step' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="chunking_plugin"]' => ['value' => 'none'],
        ],
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $form['chunking']['controls']['chunk_overlap'] = [
      '#type' => 'number',
      '#title' => 'Chunk overlap (%)',
      '#default_value' => 20,
      '#min' => 1,
      '#max' => 100,
      '#step' => 1,
      '#states' => [
        'visible' => [
          ':input[name="chunking_plugin"]' => ['value' => 'window'],
        ],
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $strategy = $form_state->getValue('chunking_plugin', 'fixed');
    $chunkMaxSize = $form_state->getValue('chunk_max_size', '500');
    $chunkOverlap = $form_state->getValue('chunk_overlap', '500') * .01;
    $chunks = $this->chunk($processed, $strategy, $chunkMaxSize, $chunkOverlap);
    $form['chunking']['controls']['count'] = [
      '#type' => 'textfield',
      '#title' => 'Chunk count',
      '#disabled' => TRUE,
      '#size' => 23,
      '#value' => count($chunks),
    ];
    $form['chunking']['preview'] = [
      '#type' => 'textarea',
      '#title' => 'Text chunks',
      '#disabled' => TRUE,
      '#rows' => 10,
      '#value' => implode('|', $chunks),
    ];

    /*
     * Step 5: Generating embeddings.
     * TBD. Considering using OpenAI API and Hugging Face Transformers Python.
     */

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
    $response->addCommand(new ReplaceCommand('#chunking-wrapper', $form['chunking']));
    return $response;
  }

  /**
   * Gets the entity of this form.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  public function fetchContent(EntityInterface $entity) {
    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $renderArray = $view_builder->view($entity, 'default');
    return $this->renderer->render($renderArray)->__toString();
  }

  public function chunk($processed, $strategy, $chunkMaxSize, $chunkOverlap) {
    $chunks = [];
    if ($strategy == 'fixed') {
      $chunks = explode("%%%", wordwrap($processed, $chunkMaxSize, "%%%", false));
    }
    else if ($strategy == 'paragraph') {
      $paragraphs = explode("\n", $processed);
      $chunks = [];
      foreach ($paragraphs as $paragraph) {
        if (empty(trim($paragraph))) continue;
        $length = strlen($paragraph);
        for ($i = 0; $i < $length; $i += $chunkMaxSize) {
            $chunks[] = substr($paragraph, $i, $chunkMaxSize);
        }
      }
    }
    else if ($strategy == 'window') {
      // Calculate the step size based on the overlap percentage
      $stepSize = (int)($chunkMaxSize * (1 - $chunkOverlap));

      // Array to hold chunks
      $chunks = [];

      // Split text into words
      $words = preg_split('/\s+/', $processed);

      // Create the initial chunk
      $currentChunk = [];
      $currentSize = 0;

      foreach ($words as $word) {
        $wordLength = strlen($word) + 1; // Adding 1 for the space or punctuation
        if ($currentSize + $wordLength > $chunkMaxSize) {
            // If adding the next word exceeds the max chunk size, finalize the current chunk
            $chunks[] = implode(' ', $currentChunk);
            // Create the new chunk with overlap
            $overlapWords = array_slice($currentChunk, -($chunkMaxSize - $stepSize));
            $currentChunk = $overlapWords;
            $currentSize = array_sum(array_map('strlen', $overlapWords)) + count($overlapWords) - 1;
            // Add the current word to the new chunk
            $currentChunk[] = $word;
            $currentSize += $wordLength;
        }
        else {
            // Add the word to the current chunk
            $currentChunk[] = $word;
            $currentSize += $wordLength;
        }
      }
      // Add the final chunk if it contains any words
      if (!empty($currentChunk)) {
        $chunks[] = implode(' ', $currentChunk);
      }
    }
    else {
      $chunks = [$processed];
    }
    return $chunks;
  }

}
