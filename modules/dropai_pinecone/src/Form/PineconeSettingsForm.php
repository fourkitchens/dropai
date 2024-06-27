<?php

namespace Drupal\dropai_pinecone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure DropAI Pinecone settings.
 */
class PineconeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dropai_pinecone.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropai_pinecone_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropai_pinecone.settings');

    $pinecone_key_gen_url = Url::fromUri('https://docs.pinecone.io/guides/projects/understanding-projects#api-keys', ['attributes' => ['target' => '_blank']]);
    $pinecone_key_gen_link = Link::fromTextAndUrl($this->t('this link'), $pinecone_key_gen_url)->toString();
    $pinecone_index_url = Url::fromUri('https://docs.pinecone.io/reference/api/control-plane/create_index', ['attributes' => ['target' => '_blank']]);
    $pinecone_index_link = Link::fromTextAndUrl($this->t('this link'), $pinecone_index_url)->toString();

    $form['pinecone_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Key'),
      '#description' => $this->t(
        'Write your Pinecone Api Key. If you don\'t have one yet, follow the steps in the @key_doc.',
        ['@key_doc' => $pinecone_key_gen_link]
      ),
      '#default_value' => $config->get('pinecone_api_key'),
      '#required' => TRUE,
    ];

    $form['pinecone_index'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index Host'),
      '#description' => $this->t(
        'Write your Pinecone Index Host. If you don\'t have one yet, follow the steps in the @index_doc.',
        ['@index_doc' => $pinecone_index_link]
      ),
      '#default_value' => $config->get('pinecone_index'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dropai_pinecone.settings')
      ->set('pinecone_api_key', $form_state->getValue('pinecone_api_key'))
      ->set('pinecone_index', $form_state->getValue('pinecone_index'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
