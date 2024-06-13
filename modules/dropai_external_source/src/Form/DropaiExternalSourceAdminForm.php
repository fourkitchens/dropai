<?php

namespace Drupal\dropai_external_source\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DropaiExternalSourceAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropai_external_source_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dropai_external_source.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropai_external_source.settings');

    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URLs'),
      '#default_value' => $config->get('urls'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['actions']['scraper'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run Scraper'),
      '#submit' => ['::runScraper'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Add validation logic here.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dropai_external_source.settings')
      ->set('urls', $form_state->getValue('urls'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Custom submit handler for the 'Run Scraper' button.
   */
  public function runScraper(array &$form, FormStateInterface $form_state) {
    // Add scraper logic here.
  }
}
