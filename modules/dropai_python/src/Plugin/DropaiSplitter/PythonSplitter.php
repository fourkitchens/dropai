<?php

namespace Drupal\dropai_python\Plugin\DropaiSplitter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dropai\Plugin\DropaiSplitterBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DropAI Splitter plugin.
 *
 * @DropaiSplitter(
 *   id = "python_splitter",
 *   label = @Translation("Python Splitter")
 * )
 */
class PythonSplitter extends DropaiSplitterBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function split(string $text, ...$args): array {
    $basePythonURL = $this->pythonConfig->get('local_python_flask_url');
    $url = "$basePythonURL/splitter";
    try {
      $response = $this->httpClient->post($url, [
        'json' => [
          'string' => $text,
          'max_characters' => $args['maxSize'],
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
