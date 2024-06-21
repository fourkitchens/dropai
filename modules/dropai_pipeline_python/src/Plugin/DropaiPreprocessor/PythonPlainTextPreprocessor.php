<?php

namespace Drupal\dropai_pipeline_python\Plugin\DropaiPreprocessor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dropai\Plugin\DropaiPreprocessorBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DropAI Preprocessor plugin to TBD.
 *
 * @DropaiPreprocessor(
 *   id = "python_plain_text_preprocessor",
 *   label = @Translation("Python Plain Text")
 * )
 */
class PythonPlainTextPreprocessor extends DropaiPreprocessorBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The HTTP client to fetch the URL.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a PythonPlainTextProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle HTTP Client.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Client $http_client,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(string $text): string {
    $url = 'http://python.lndo.site/html-to-text';
    try {
      $response = $this->httpClient->post($url, [
        'json' => [
          'string' => $text,
        ],
      ],);
      $body = json_decode($response->getBody()->__toString(), TRUE)['data'];
    }
    catch (\Exception $e) {
      $body = $this->t('An error occurred: @message', ['@message' => $e->getMessage()]);
    }

    return $body;

  }

}
