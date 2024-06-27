<?php

namespace Drupal\dropai_python\Plugin\DropaiTokenizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dropai\Plugin\DropaiTokenizerBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DropAI Tokenizer plugin using a Python Tiktoken library.
 *
 * @DropaiTokenizer(
 *   id = "python_tiktoken",
 *   label = @Translation("Python Tiktoken")
 * )
 */
class PythonTiktokenTokenizer extends DropaiTokenizerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The HTTP client to fetch the URL.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $pythonConfig;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory interface.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Client $http_client,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->pythonConfig = $config_factory->get('dropai_python.settings');
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
      $container->get('config.factory'),
    );
  }

  /**
   * Models supported by the tokenizer as Id/name key/value pairs.
   *
   * @var array
   */
  public $models = [
    'gpt-4' => 'GPT-4',
    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    'text-embedding-ada-002' => 'Text Embedding ADA-002',
    'text-embedding-3-small' => 'Text Embedding 3 Small',
    'text-embedding-3-large' => 'Text Embedding 3 Large',
  ];

  /**
   * {@inheritdoc}
   */
  public function tokenize(string $text, string $model): array {
    $basePythonURL = $this->pythonConfig->get('local_python_flask_url');
    $url = "$basePythonURL/tokens";
    try {
      $response = $this->httpClient->post($url, [
        'json' => [
          'string' => $text,
          'encoding' => 'cl100k_base',
          'model' => $model,
        ],
      ],);
      $body = json_decode($response->getBody()->__toString(), TRUE)['data'];
    }
    catch (\Exception $e) {
      $body = $this->t('An error occurred. Make sure the local Python Flask app is running. (from the root, run: lando python web/modules/contrib/dropai/modules/dropai_python/python/app.py) Message: @message', ['@message' => $e->getMessage()]);
    }

    return $body;
  }

}
