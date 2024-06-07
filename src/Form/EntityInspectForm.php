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
    // TODO: interface for picking entities and content types.  maybe display modes?
    // Todo: exclude from index rules.
    // /bulk actions
    // upsert and delete
    // batch, queue, and override button
    $source = $this->processContentBody($this->entity);
    $form['source_rendered'] = [
      '#type' => 'textarea',
      '#title' => 'Rendered Source',
      '#disabled' => TRUE,
      '#rows' => 10,
      '#value' => $source,
    ];
    $form['cleaner'] = [
      '#type' => 'select',
      '#title' => 'Preprocessing',
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

    $original = $source->__toString();
    $cleaner = $form_state->getValue('cleaner', 'plain');
    if ($cleaner == 'markdown') {
      $converter = new HtmlConverter(['strip_tags' => true]);
      $transformed = $converter->convert($original);
    }
    else {
      $html = new Html2Text($original, ['width' => 0]);
      $transformed = $html->getText();
    }
    // Clean up the text by removing extra whitespace.
    $clean = preg_replace("/[\r\n]+/", "\n", $transformed);
    $clean = trim($clean);

    $form['cleaner_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'cleaner-wrapper'],
    ];
    $form['cleaner_wrapper']['source_clean'] = [
      '#type' => 'textarea',
      '#title' => 'Plain Text Source',
      '#disabled' => TRUE,
      '#rows' => 10,
      '#value' => $clean,
    ];

    // Todo: Add user friendly selection tool.
    // Todo: Add Llama tokenizer so it's not all OpenAI
    // https://github.com/openai/openai-cookbook/blob/main/examples/How_to_count_tokens_with_tiktoken.ipynb
    $form['model'] = [
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

    $model = $form_state->getValue('model', 'gpt-3.5-turbo-0301');
    $provider = new EncoderProvider();
    $encoder = $provider->getForModel($model);
    $tokens = $encoder->encode($clean);
    // Wrap the token-related fields in a div for AJAX updating.
    $form['token_count_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'token-count-wrapper'],
    ];
    $form['token_count_wrapper']['char_count'] = [
      '#type' => 'textfield',
      '#title' => 'Characters',
      '#disabled' => TRUE,
      '#value' => strlen($clean),
    ];
    $form['token_count_wrapper']['token_count'] = [
      '#type' => 'textfield',
      '#title' => 'Tokens',
      '#disabled' => TRUE,
      '#value' => count($tokens),
    ];
    $form['token_count_wrapper']['tokens'] = [
      '#type' => 'textarea',
      '#title' => 'Tokens',
      '#disabled' => TRUE,
      '#rows' => 10,
      '#value' => '[' . implode(', ', $tokens) . ']',
    ];
    $form['token_count_wrapper']['tokens_textual'] = [
      '#type' => 'textarea',
      '#title' => 'Tokens as text',
      '#disabled' => TRUE,
      '#rows' => 10,
      '#value' => implode('|', $encoder->decode2($tokens)),
    ];

    $form['chunking'] = [
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
    $form['chunk_max_size'] = [
      '#type' => 'number',
      '#title' => 'Chunk max size (character)',
      '#default_value' => 500,
      '#min' => 1,
      '#step' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="chunking"]' => ['value' => 'none'],
        ],
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];
    $form['chunk_overlap'] = [
      '#type' => 'number',
      '#title' => 'Chunk overlap (%)',
      '#default_value' => 20,
      '#min' => 1,
      '#max' => 100,
      '#step' => 1,
      '#states' => [
        'visible' => [
          ':input[name="chunking"]' => ['value' => 'window'],
        ],
      ],
      '#ajax' => [
        'callback' => '::updateFormParts',
        'event' => 'change',
      ],
    ];

    $maxChunkSize = $form_state->getValue('chunk_max_size', '500');
    $strategy = $form_state->getValue('chunking', 'fixed');
    if ($strategy == 'fixed') {
      $chunks = explode("%%%", wordwrap($clean, $maxChunkSize, "%%%", false));
    }
    else if ($strategy == 'paragraph') {
      $paragraphs = explode("\n", $clean);
      $chunks = [];
      foreach ($paragraphs as $paragraph) {
        if (empty(trim($paragraph))) continue;
        $length = strlen($paragraph);
        for ($i = 0; $i < $length; $i += $maxChunkSize) {
            $chunks[] = substr($paragraph, $i, $maxChunkSize);
        }
      }
    }
    else if ($strategy == 'window') {
      // TODO: Review this code. Something seems off with the first chunks.
      $overlap = $form_state->getValue('chunk_overlap', '20') * .01;
      // Calculate the step size based on the overlap percentage
      $stepSize = (int)($maxChunkSize * (1 - $overlap));

      // Array to hold chunks
      $chunks = [];

      // Split text into words
      $words = preg_split('/\s+/', $clean);

      // Create the initial chunk
      $currentChunk = [];
      $currentSize = 0;

      foreach ($words as $word) {
        $wordLength = strlen($word) + 1; // Adding 1 for the space or punctuation
        if ($currentSize + $wordLength > $maxChunkSize) {
            // If adding the next word exceeds the max chunk size, finalize the current chunk
            $chunks[] = implode(' ', $currentChunk);
            // Create the new chunk with overlap
            $overlapWords = array_slice($currentChunk, -($maxChunkSize - $stepSize));
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
      $chunks = [$clean];
    }

    $form['chunks_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'chunks-wrapper'],
    ];
    $form['chunks_wrapper']['count'] = [
      '#type' => 'textfield',
      '#title' => 'Chunk count',
      '#disabled' => TRUE,
      '#value' => count($chunks),
    ];
    $form['chunks_wrapper']['text'] = [
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
    $response->addCommand(new ReplaceCommand('#cleaner-wrapper', $form['cleaner_wrapper']));
    $response->addCommand(new ReplaceCommand('#token-count-wrapper', $form['token_count_wrapper']));
    $response->addCommand(new ReplaceCommand('#chunks-wrapper', $form['chunks_wrapper']));
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

  protected function processContentBody(EntityInterface $entity) {
    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $renderArray = $view_builder->view($entity, 'default');
    return $this->renderer->render($renderArray);
  }

}
