<?php

namespace Drupal\dropai\Form;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Html2Text\Html2Text;
use Yethee\Tiktoken\EncoderProvider;
use League\HTMLToMarkdown\HtmlConverter;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Implements an entity Inspect form.
 */
class EntityInspectForm extends FormBase {

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
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    MessengerInterface $messenger,
    AccountProxyInterface $currentUser,
    RendererInterface $renderer
  ) {
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
      '#default_value' => 'plain',
      '#options' => [
        'plain' => 'Plain Text',
        'markdown' => 'Markdown',
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $preprocessor = $form_state->getValue('preprocessor_plugin', 'plain');
    $processed = $this->preprocess($source, $preprocessor);
    $form['preprocessor']['source_processed'] = [
      '#theme' => 'code_block',
      '#language' => 'markdown',
      '#code' => $processed,
    ];

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
    $form['tokenizer']['controls']['tokenizer_model'] = [
      '#type' => 'select',
      '#title' => 'Model',
      '#default_value' => 'gpt-3.5-turbo-0301',
      '#options' => [
        'gpt-4o' => 'gpt-4o',
        'gpt-4' => 'gpt-4',
        'gpt-3.5-turbo' => 'gpt-3.5-turbo',
        'text-embedding-ada-002' => 'text-embedding-ada-002',
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $model = $form_state->getValue('tokenizer_model', 'gpt-3.5-turbo-0301');
    $tokens = $this->tokenize($processed, $model);
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
    $response = new AjaxResponse();

    // Rebuild the cleaner wrapper.
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

  public function preprocess($source, $preprocessor) {
    if ($preprocessor == 'markdown') {
      $converter = new HtmlConverter(['strip_tags' => true]);
      $transformed = $converter->convert($source);
    }
    else {
      $html = new Html2Text($source, ['width' => 0]);
      $transformed = $html->getText();
    }
    // Clean up the text by removing extra whitespace.
    $clean = preg_replace("/[\r\n]+/", "\n", $transformed);
    $clean = trim($clean);
    return $clean;
  }

  public function tokenize($processed, $model) {
    $provider = new EncoderProvider();
    $encoder = $provider->getForModel($model);
    return $encoder->encode($processed);
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
