<?php

namespace Drupal\dropai_chatgpt\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dropai_chatgpt\Service\ChatCompletions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ChatGpt Chat Form.
 */
class ChatGptChatForm extends FormBase {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The example service.
   *
   * @var \Drupal\dropai_chatgpt\Service\ChatCompletions
   */
  protected $chatCompletions;

  /**
   * Cache name.
   *
   * @var string
   */
  protected $cacheName;

  /**
   * Constructs a new AjaxConcatForm.
   *
   * @param \Drupal\dropai_chatgpt\Service\ChatCompletions
   *   The example service.
   */
  public function __construct(AccountProxyInterface $currentUser, CacheBackendInterface $cache_backend, ChatCompletions $chatCompletions) {
    $this->currentUser = $currentUser;
    $this->cache = $cache_backend;
    $this->chatCompletions = $chatCompletions;
    $this->cacheName = 'dropai_chatgpt.chat_completions-' . base64_encode($this->currentUser->id() . '-' . time());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('cache.default'),
      $container->get('dropai_chatgpt.chat_completions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropai_chatgpt_chat_completions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropai_chatgpt.settings');
    $models = $this->chatCompletions->getModels();

    $form['chatgpt_instructions_container'] = [
      '#type' => 'fieldset',
      '#title' => t('INSTRUCTIONS'),
    ];

    $form['chatgpt_instructions_container']['chatgpt_instructions'] = [
      '#type' => 'item',
      '#markup' => $config->get('chatgpt_instructions'),
    ];

    $form['chatgpt_settings_container'] = [
      '#type' => 'fieldset',
      '#title' => t('SETTINGS'),
    ];

    $form['chatgpt_settings_container']['chatgpt_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#description' => $this->t('The OpenAI API is powered by a diverse set of models with different capabilities and price points.'),
      '#options' => $models,
      '#default_value' => $config->get('chatgpt_model') ?: 'gpt-4o',
      '#required' => TRUE,
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container'] = [
      '#type' => 'details',
      '#title' => t('Advanced Settings'),
      '#open' => FALSE,
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#description' => $this->t('Lower values for temperature result in more consistent outputs (e.g. 0.2), while higher values generate more diverse and creative results (e.g. 1.0). Select a temperature value based on the desired trade-off between coherence and creativity for your specific application. The temperature can range is from 0 to 2.'),
      '#min' => 0,
      '#max' => 2,
      '#step' => .01,
      '#default_value' => $config->get('chatgpt_temperature') ?: 1,
      '#required' => TRUE,
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_maximum_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Tokens'),
      '#description' => $this->t('The maximum number of tokens to generate shared between the prompt and completion. The exact limit varies by model. (One token is roughly 4 characters for standard English text).'),
      '#min' => 1,
      '#max' => 16384,
      '#step' => 1,
      '#default_value' => $config->get('chatgpt_maximum_tokens') ?: 256,
      '#required' => TRUE,
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_top_p'] = [
      '#type' => 'number',
      '#title' => $this->t('Top P'),
      '#description' => $this->t('Controls diversity via nucleus sampling: 0.5 means half of all likelihood-weighted options are considered.'),
      '#min' => 0,
      '#max' => 1,
      '#step' => .01,
      '#default_value' => $config->get('chatgpt_top_p') ?: 1,
      '#required' => TRUE,
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_penalty'] = [
      '#type' => 'fieldset',
      '#title' => t('Penalty'),
      '#description' => $this->t('The frequency and presence penalties can be used to reduce the likelihood of sampling repetitive sequences of tokens.'),
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_penalty']['chatgpt_frequency_penalty'] = [
      '#type' => 'number',
      '#title' => $this->t('Frequency Penalty'),
      '#min' => 0,
      '#max' => 1,
      '#step' => .01,
      '#default_value' => $config->get('chatgpt_frequency_penalty') ?: 0,
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_penalty']['chatgpt_presence_penalty'] = [
      '#type' => 'number',
      '#title' => $this->t('Presence Penalty'),
      '#min' => 0,
      '#max' => 1,
      '#step' => .01,
      '#default_value' => $config->get('chatgpt_presence_penalty') ?: 0,
    ];

    $form['chatgpt_chat_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CHAT'),
      '#prefix' => '<div id="chat-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['chatgpt_chat_container']['chatgpt_chat'] = [
      '#type' => 'textarea',
      '#default_value' => '',
      '#rows' => 20,
      '#attributes'=> ['readonly' => TRUE],
    ];

    $form['chatgpt_input_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('INPUT'),
    ];

    $form['chatgpt_input_container']['chatgpt_input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter a message'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['chatgpt_input_container']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Send'),
      '#ajax' => [
        'callback' => '::ajaxAskCallback',
        'wrapper' => 'chat-wrapper',
      ],
    ];

    // Attach the JavaScript library.
    $form['#attached']['library'][] = 'dropai_chatgpt/chatgpt-textarea-scroll-down';

    // Disable cache for the form.
    $form['#cache'] = [
      'max-age' => 0,
    ];

    return $form;
  }

  /**
   * Ajax function.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function ajaxAskCallback(array &$form, FormStateInterface &$form_state) {
    $response = new AjaxResponse();
    $chatMessages = $form_state->getValue('chatgpt_chat');
    $chatInput = trim($form_state->getValue('chatgpt_input'));

    // Get ChatGPT configuration.
    $gptModel = $form_state->getValue('chatgpt_model');
    $gptTemperature = $form_state->getValue('chatgpt_temperature');
    $gptMaximumTokens = $form_state->getValue('chatgpt_maximum_tokens');
    $gptTopP = $form_state->getValue('chatgpt_top_p');
    $gptFrequencyPenalty = $form_state->getValue('chatgpt_frequency_penalty');
    $gptPresencePenalty = $form_state->getValue('chatgpt_presence_penalty');

    // Load the chat cache.
    $cacheData = $this->cache->get($this->cacheName);
    if ($cacheData && isset($cacheData->data) && is_array($cacheData->data)) {
      $messageHistory = $cacheData->data;
    }
    else {
      $messageHistory = [];
    }

    // Check that the input is not empty.
    if (empty($chatInput)) {
      return;
    }

    // Add the ask to the history.
    $messageHistory[] = [
      'role' => 'user',
      'content' => $chatInput,
    ];

    // Send the messages and get ChatGTP response.
    $chatResponse = $this->chatCompletions->getChatCompletion(
      $messageHistory,
      $gptModel,
      $gptTemperature,
      $gptMaximumTokens,
      $gptTopP,
      $gptFrequencyPenalty,
      $gptPresencePenalty 
    );
    // Write the user question in the chat textarea.
    $chatMessages .= $this->t('YOU SAY:') . $this->newLines(2) . $chatInput;
    // Write the ChatGTP response in the chat textarea.
    $chatMessages .= $this->newLines(3) . $this->t('CHATGPT SAYS:') . $this->newLines(2). $chatResponse . $this->newLines(3);

    // Add the response to the history.
    $messageHistory[] = [
      'role' => 'assistant',
      'content' => $chatResponse,
    ];
    // Save the cache.
    $this->cache->set($this->cacheName, $messageHistory, time() + 900);

    // Update the textarea value.
    $form['chatgpt_chat_container']['chatgpt_chat']['#value'] = $chatMessages;

    // Replace the textarea with the new content.
    $response->addCommand(new HtmlCommand('.form-item-chatgpt-chat', $form['chatgpt_chat_container']['chatgpt_chat']));

    // Clear the textfield value.
    $response->addCommand(new InvokeCommand('#edit-chatgpt-input', 'val', ['']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Returns new lines.
   */
  private function newLines($qty) {
    $output = '';
    for ($i = 0; $i < $qty; $i++) {
      $output .= PHP_EOL;
    }
    return $output;
  }

}
