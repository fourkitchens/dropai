<?php

namespace Drupal\dropai_chatgpt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\dropai_chatgpt\Service\ChatCompletions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure DropAI ChatGPT settings.
 */
class ChatGptSettingsForm extends ConfigFormBase {

  /**
   * The example service.
   *
   * @var \Drupal\dropai_chatgpt\Service\ChatCompletions
   */
  protected $chatCompletions;

  /**
   * Constructs the form settings.
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
  protected function getEditableConfigNames() {
    return ['dropai_chatgpt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropai_chatgpt_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropai_chatgpt.settings');
    $config_openai = $this->config('dropai_openai.settings');
    $models = $this->chatCompletions->getModels();

    if (empty($config_openai->get('api_key'))) {
      $chatgpt_config_url = Url::fromRoute('dropai_chatgpt.settings')->toString();
      $openai_config_url = Url::fromRoute('dropai_openai.settings', [], ['query' => ['destination' => $chatgpt_config_url]]);
      $openai_config_link = Link::fromTextAndUrl($this->t('Configuration Form'), $openai_config_url)->toString();
      $openai_key_gen_url = Url::fromUri('https://platform.openai.com/api-keys', ['attributes' => ['target' => '_blank']]);
      $openai_key_gen_link = Link::fromTextAndUrl($this->t('OpenAI Console'), $openai_key_gen_url)->toString();

      $form['chatgpt_missing_api_key'] = [
        '#type' => 'item',
        '#title' => $this->t('Missing API Key'),
        '#markup' => $this->t(
          'You haven\'t entered your OpenAI API Key. Please generate a key in the @openai_key_gen_link and add it to the @openai_config_link.',
          [
            '@openai_key_gen_link' => $openai_key_gen_link,
            '@openai_config_link' => $openai_config_link,
          ]
        ),
      ];

      return $form;
    }

    // Define documentation links.
    $openai_models_url = Url::fromUri('https://platform.openai.com/docs/models', ['attributes' => ['target' => '_blank']]);
    $openai_models_link = Link::fromTextAndUrl($this->t('models documentation'), $openai_models_url)->toString();
    $openai_chat_completitions_url = Url::fromUri('https://platform.openai.com/docs/api-reference/chat/create', ['attributes' => ['target' => '_blank']]);
    $openai_chat_completitions_link = Link::fromTextAndUrl($this->t('Chat Completions API'), $openai_chat_completitions_url)->toString();
    $openai_legacy_completitions_url = Url::fromUri('https://platform.openai.com/docs/api-reference/completions', ['attributes' => ['target' => '_blank']]);
    $openai_legacy_completitions_link = Link::fromTextAndUrl($this->t('Legacy Completions API'), $openai_legacy_completitions_url)->toString();

    $form['chatgpt_instructions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('System Instructions'),
      '#description' => $this->t('Write the special instructions to start the Chat with GPT.'),
      '#default_value' => $config->get('chatgpt_instructions'),
      '#required' => FALSE,
    ];

    $form['chatgpt_models'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Models'),
      '#description' => $this->t(
        'The OpenAI API is powered by a diverse set of models with different capabilities and price points. See the @openai_models_link.',
        [
          '@openai_models_link' => $openai_models_link,
        ]
      ),
      '#options' => $models,
      '#default_value' => $config->get('chatgpt_models') ?: ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o'],
      '#required' => TRUE,
    ];

    $form['chatgpt_default_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Model'),
      '#description' => $this->t('Select the default model.'),
      '#options' => $models,
      '#default_value' => $config->get('chatgpt_default_model') ?: 'gpt-4o',
      '#required' => TRUE,
    ];

    $form['chatgpt_temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#description' => $this->t('Lower values for temperature result in more consistent outputs (e.g. 0.2), while higher values generate more diverse and creative results (e.g. 1.0). Select a temperature value based on the desired trade-off between coherence and creativity for your specific application. The temperature can range is from 0 to 2.'),
      '#min' => 0,
      '#max' => 2,
      '#step' => .01,
      '#default_value' => $config->get('chatgpt_temperature') ?: 1,
      '#required' => TRUE,
    ];

    $form['chatgpt_maximum_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Tokens'),
      '#description' => $this->t('The maximum number of tokens to generate shared between the prompt and completion. The exact limit varies by model. (One token is roughly 4 characters for standard English text).'),
      '#min' => 1,
      '#max' => 16384,
      '#step' => 1,
      '#default_value' => $config->get('chatgpt_maximum_tokens') ?: 256,
      '#required' => TRUE,
    ];

    $form['chatgpt_top_p'] = [
      '#type' => 'number',
      '#title' => $this->t('Top P'),
      '#description' => $this->t('Controls diversity via nucleus sampling: 0.5 means half of all likelihood-weighted options are considered.'),
      '#min' => 0,
      '#max' => 1,
      '#step' => .01,
      '#default_value' => $config->get('chatgpt_top_p') ?: 1,
      '#required' => TRUE,
    ];

    $form['chatgpt_penalty'] = [
      '#type' => 'fieldset',
      '#title' => t('Penalty'),
      '#description' => $this->t(
        'The frequency and presence penalties found in the @openai_chat_completitions_link and @openai_legacy_completitions_link can be used to reduce the likelihood of sampling repetitive sequences of tokens.',
        [
          '@openai_chat_completitions_link' => $openai_chat_completitions_link,
          '@openai_legacy_completitions_link' => $openai_legacy_completitions_link,
        ]
      ),
    ];

    $form['chatgpt_penalty']['chatgpt_frequency_penalty'] = [
      '#type' => 'number',
      '#title' => $this->t('Frequency Penalty'),
      '#min' => 0,
      '#max' => 1,
      '#step' => .01,
      '#default_value' => $config->get('chatgpt_frequency_penalty') ?: 0,
      '#required' => TRUE,
    ];

    $form['chatgpt_penalty']['chatgpt_presence_penalty'] = [
      '#type' => 'number',
      '#title' => $this->t('Presence Penalty'),
      '#min' => 0,
      '#max' => 1,
      '#step' => .01,
      '#default_value' => $config->get('chatgpt_presence_penalty') ?: 0,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $chatgpt_models = array_filter($form_state->getValue('chatgpt_models'), function($val) {
      return $val !== 0;
    });

    $this->config('dropai_chatgpt.settings')
      ->set('chatgpt_instructions', $form_state->getValue('chatgpt_instructions'))
      ->set('chatgpt_models', array_values($chatgpt_models))
      ->set('chatgpt_default_model', $form_state->getValue('chatgpt_default_model'))
      ->set('chatgpt_temperature', $form_state->getValue('chatgpt_temperature'))
      ->set('chatgpt_maximum_tokens', $form_state->getValue('chatgpt_maximum_tokens'))
      ->set('chatgpt_top_p', $form_state->getValue('chatgpt_top_p'))
      ->set('chatgpt_frequency_penalty', $form_state->getValue('chatgpt_frequency_penalty'))
      ->set('chatgpt_presence_penalty', $form_state->getValue('chatgpt_presence_penalty'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
