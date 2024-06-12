<?php

namespace Drupal\dropai\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an entity Inspect form.
 */
class PdfReaderSettingsForm extends ConfigFormBase {

  protected $moduleHandler;

  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dropai.pdf_reader.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropai_pdf_reader_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dropai.pdf_reader.settings');

    $form['pdf_library'] = [
      '#type' => 'select',
      '#title' => $this->t('PDF Library'),
      '#description' => $this->t('Select the PDF library to use for conversion.'),
      '#options' => $this->discoverPdfReaders(),
      '#default_value' => $config->get('pdf_library') ?: 'pdfparser',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dropai.pdf_reader.settings')
      ->set('pdf_library', $form_state->getValue('pdf_library'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Discover PDF readers in the plugin directory.
   */
  private function discoverPdfReaders() {
    $directory = $this->moduleHandler->getModule('dropai')->getPath() . '/src/Plugin/PdfReader';
    $files = scandir($directory);
    $readers = [];

    foreach ($files as $file) {
      if (preg_match('/^[A-Za-z0-9]+Reader\.php$/', $file)) {
        $readerFile = str_replace('.php', '', $file);
        $readerName = str_replace('Reader.php', '', $file);
        $readers[$readerFile] = $readerName;
      }
    }

    return $readers;
  }

}
