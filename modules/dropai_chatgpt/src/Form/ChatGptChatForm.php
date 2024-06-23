<?php

namespace Drupal\dropai_chatgpt\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\dropai_chatgpt\Service\ChatCompletions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ChatGpt Chat Form.
 */
class ChatGptChatForm extends FormBase {

  /**
   * The example service.
   *
   * @var \Drupal\dropai_chatgpt\Service\ChatCompletions
   */
  protected $chatCompletions;

  /**
   * Constructs a new AjaxConcatForm.
   *
   * @param \Drupal\dropai_chatgpt\Service\ChatCompletions
   *   The example service.
   */
  public function __construct(ChatCompletions $chatCompletions) {
    $this->chatCompletions = $chatCompletions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('dropai_chatgpt.chat_completions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropai_chatgpt_chat_form';
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
        'callback' => '::ajaxConcatCallback',
        'wrapper' => 'textarea-wrapper',
      ],
    ];

    $form['#prefix'] = '<div id="textarea-wrapper">';
    $form['#suffix'] = '</div>';

    // Attach the JavaScript library.
    $form['#attached']['library'][] = 'dropai_chatgpt/chatgpt-textarea-scroll-down';

    return $form;
  }

  /**
   * Undocumented function
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function ajaxConcatCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $chatHistory = $form_state->getValue('chatgpt_chat');
    $model = $form_state->getValue('chatgpt_model');
    $chatgptInput = trim($form_state->getValue('chatgpt_input'));

    if (empty($chatgptInput)) {
      return;
    }

    if (!empty($chatHistory)) {
      $chatHistory .= PHP_EOL . PHP_EOL . PHP_EOL;
    }

    $messages = [];
    $messages[] = [
      'role' => 'user',
      'content' => $chatgptInput,
    ];
    $chatResponse = $this->chatCompletions->getChatCompletion($model, $messages);

    $chatHistory .= $this->t('YOU SAY:') . PHP_EOL . PHP_EOL . $chatgptInput;
    $chatHistory .= PHP_EOL . PHP_EOL . PHP_EOL . $this->t('CHATGPT SAYS:') . PHP_EOL . PHP_EOL . $chatResponse;

    // Update the textarea value.
    $form['chatgpt_chat_container']['chatgpt_chat']['#value'] = $chatHistory;

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
   * {@inheritdoc}
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
