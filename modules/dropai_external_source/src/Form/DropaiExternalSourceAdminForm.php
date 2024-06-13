<?php

namespace Drupal\dropai_external_source\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Provides a form for managing DropAI external sources.
 *
 * This form allows users to enter a list of URLs to scrape.
 * When the 'Run scraper' button is clicked, the form fetches each URL,
 * scrapes the title and body content,
 * and saves this data to a new Drupal node.
 * The form also logs each scrape action to the Drupal database log.
 *
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class DropaiExternalSourceAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'dropai_external_source_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['dropai_external_source.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('dropai_external_source.settings');

    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URLs'),
      '#default_value' => $config->get('urls'),
      '#description' => $this->t('Enter the URLs of the sites you wish to scrape. Please ensure that each URL is on a separate line.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['actions']['scraper'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run scraper'),
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('dropai_external_source.settings')
      ->set('urls', $form_state->getValue('urls'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Custom submit handler for the 'Run scraper' button.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function runScraper(array &$form, $form_state): void {
    $urls = $form_state->getValue('urls');
    $urls = explode("\n", $urls);
    $client = new Client();

    foreach ($urls as $url) {
      $url = trim($url);

      if (!empty($url)) {
        // Make a request to the URL.
        $response = $client->request('GET', $url);
        $crawler = new Crawler((string) $response->getBody());
        $title = $crawler->filter('title')->text();
        $body = $crawler->filter('body')->text();

        // Create a new node.
        $node = Node::create([
          'type' => 'page',
          'title' => $title,
          'body' => [
            'value' => $body,
            'format' => 'full_html',
          ],
        ]);

        // Save the node.
        $node->save();

        // Log the scrape action to the dblog.
        \Drupal::logger('dropai_external_source')
          ->notice('Scraped title from @url: @title', [
            '@url' => $url,
            '@title' => $title,
          ]);
      }
    }
  }

}
