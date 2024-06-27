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
   * The ChatCompletions Service.
   *
   * @var \Drupal\dropai_chatgpt\Service\ChatCompletions
   */
  protected $chatCompletions;

  /**
   * Config variable of the ChatGTP Settings form.
   *
   * @var Object
   */
  protected $chatgptSettings;

  /**
   * Constructs a new AjaxConcatForm.
   *
   * @param \Drupal\dropai_chatgpt\Service\ChatCompletions
   *   The example service.
   */
  public function __construct(AccountProxyInterface $currentUser, ChatCompletions $chatCompletions) {
    $this->currentUser = $currentUser;
    $this->chatCompletions = $chatCompletions;
    $this->chatgptSettings = $this->config('dropai_chatgpt.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
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
    $models = $this->chatgptSettings->get('chatgpt_models') ?: ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o'];

    $form['chatgpt_settings_container'] = [
      '#type' => 'fieldset',
      '#title' => t('SETTINGS'),
    ];

    $form['chatgpt_settings_container']['chatgpt_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#description' => $this->t('The OpenAI API is powered by a diverse set of models with different capabilities and price points.'),
      '#options' => array_combine($models, $models),
      '#default_value' => $this->chatgptSettings->get('chatgpt_default_model') ?: 'gpt-4o',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxSetMaxTokensCallback',
        'event' => 'change',
        'wrapper' => 'max-tokens-wrapper',
      ],
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container'] = [
      '#type' => 'details',
      '#title' => t('Advanced Settings'),
      '#open' => FALSE,
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_maximum_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Tokens'),
      '#description' => $this->t('The maximum number of tokens to generate shared between the prompt and completion. The exact limit varies by model. (One token is roughly 4 characters for standard English text).'),
      '#min' => 1,
      '#max' => 16384,
      '#step' => 1,
      '#default_value' => $this->chatgptSettings->get('chatgpt_maximum_tokens') ?: 256,
      '#required' => TRUE,
      '#prefix' => '<div id="max-tokens-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#description' => $this->t('Lower values for temperature result in more consistent outputs (e.g. 0.2), while higher values generate more diverse and creative results (e.g. 1.0). Select a temperature value based on the desired trade-off between coherence and creativity for your specific application. The temperature can range is from 0 to 2.'),
      '#min' => 0,
      '#max' => 2,
      '#step' => .01,
      '#default_value' => $this->chatgptSettings->get('chatgpt_temperature') ?: 1,
      '#required' => TRUE,
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_top_p'] = [
      '#type' => 'number',
      '#title' => $this->t('Top P'),
      '#description' => $this->t('Controls diversity via nucleus sampling: 0.5 means half of all likelihood-weighted options are considered.'),
      '#min' => 0,
      '#max' => 1,
      '#step' => .01,
      '#default_value' => $this->chatgptSettings->get('chatgpt_top_p') ?: 1,
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
      '#default_value' => $this->chatgptSettings->get('chatgpt_frequency_penalty') ?: 0,
    ];

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_penalty']['chatgpt_presence_penalty'] = [
      '#type' => 'number',
      '#title' => $this->t('Presence Penalty'),
      '#min' => 0,
      '#max' => 1,
      '#step' => .01,
      '#default_value' => $this->chatgptSettings->get('chatgpt_presence_penalty') ?: 0,
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
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#ajax' => [
        'callback' => '::ajaxAskCallback',
        'wrapper' => 'chat-wrapper',
      ],
    ];

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
  public function ajaxAskCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $errors = $form_state->getErrors();
    if (!empty($errors)) {
      return $response;
    }

    // Attach the JavaScript library.
    $form['chatgpt_chat_container']['#attached']['library'][] = 'dropai_chatgpt/chatgpt-textarea-scroll-down';
    // Update the Chat data.
    $form['chatgpt_chat_container']['chatgpt_chat']['#value'] = $form_state->getValue('chatgpt_chat');
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
    $chatMessages = $form_state->getValue('chatgpt_chat');
    $chatInput = trim($form_state->getValue('chatgpt_input'));

    // Get ChatGPT configuration.
    $gptModel = $form_state->getValue('chatgpt_model');
    $gptTemperature = $form_state->getValue('chatgpt_temperature');
    $gptMaximumTokens = $form_state->getValue('chatgpt_maximum_tokens');
    $gptTopP = $form_state->getValue('chatgpt_top_p');
    $gptFrequencyPenalty = $form_state->getValue('chatgpt_frequency_penalty');
    $gptPresencePenalty = $form_state->getValue('chatgpt_presence_penalty');

    // Get the form Storage.
    $storage = $form_state->getStorage();
    if (isset($storage['messageHistory']) && !empty($storage['messageHistory'])) {
      $messageHistory = $storage['messageHistory'];
    }
    else {
      // If the Storage is empty, load the default message.
      $messageHistory = [];

      if (!empty($this->chatgptSettings->get('chatgpt_instructions'))) {
        $messageHistory[] = [
          'role' => 'system',
          'content' => trim($this->chatgptSettings->get('chatgpt_instructions')),
        ];
      }
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
    $chatMessages .= $this->formatMessage('client', $chatInput);
    // Write the ChatGTP response in the chat textarea.
    $chatMessages .= $this->formatMessage('gpt', $chatResponse);

    // Add the response to the history.
    $messageHistory[] = [
      'role' => 'assistant',
      'content' => $chatResponse,
    ];

    $form_state->setValue('chatgpt_chat', $chatMessages);
    $storage['messageHistory'] = $messageHistory;
    $form_state->setStorage($storage);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax function: Set max tokens.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function ajaxSetMaxTokensCallback(array &$form, FormStateInterface $form_state) {
    $model = $form_state->getValue('chatgpt_model');
    $maxTokens = $form_state->getValue('chatgpt_maximum_tokens');

    switch ($model) {
      case 'gpt-4':
      case 'gpt-4-0613':
        $maxValue = 8192;
        $defValue = 4096;
        break;

      case 'gpt-4-turbo':
      case 'gpt-4-turbo-2024-04-09':
      case 'gpt-4-turbo-preview':
      case 'gpt-4-0125-preview':
      case 'gpt-4-1106-preview':
      case 'gpt-4o':
      case 'gpt-4o-2024-05-13':
        $maxValue = 128000;
        $defValue = 4096;
        break;

      case 'gpt-3.5-turbo':
      case 'gpt-3.5-turbo-0125':
      case 'gpt-3.5-turbo-1106':
        $maxValue = 16384;
        $defValue = 4096;
        break;

      case 'gpt-3.5-turbo-instruct':
        $maxValue = 4096;
        $defValue = 1024;
        break;

      default:
        $maxValue = 1024;
        $defValue = 512;
    }

    $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_maximum_tokens']['#max'] = $maxValue;
    if ($maxTokens > $defValue) {
      $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_maximum_tokens']['#value'] = $defValue;
    }

    return $form['chatgpt_settings_container']['chatgpt_advanced_settings_container']['chatgpt_maximum_tokens'];
  }

  /**
   * Set message format.
   */
  private function formatMessage($user, $message) {
    $output = '';
    $output .= $user === 'client' ? $this->t('YOU SAY:') : $this->t('CHATGPT SAYS:');
    $output .= PHP_EOL . PHP_EOL . $message . PHP_EOL . PHP_EOL . PHP_EOL;
    return $output;
  }

}
