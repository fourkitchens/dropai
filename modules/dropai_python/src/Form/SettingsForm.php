<?php

namespace Drupal\dropai_python\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure DropAI Python settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dropai_python.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropai_python_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropai_python.settings');

    $form['local_python_flask_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Local Python Flask URL'),
      '#default_value' => $config->get('local_python_flask_url') ?: 'http://python.lndo.site',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dropai_python.settings')
      ->set('local_python_flask_url', rtrim($form_state->getValue('local_python_flask_url'), "/"))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
