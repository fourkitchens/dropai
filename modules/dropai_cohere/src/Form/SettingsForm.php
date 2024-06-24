<?php

namespace Drupal\dropai_cohere\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure DropAI Cohere settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dropai_cohere.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropai_cohere_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropai_cohere.settings');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cohere API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dropai_cohere.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
