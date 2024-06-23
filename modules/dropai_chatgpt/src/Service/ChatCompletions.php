<?php

namespace Drupal\dropai_chatgpt\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class ChatCompletions {

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * OpenAI Api Key.
   *
   * @var string
   */
  private $apiKey;

  /**
   * OpenAI Api Completitions URL.
   *
   * @var string
   */
  private $apiCompletionsUrl;

  /**
   * OpenAI Api Models URL.
   *
   * @var string
   */
  private $apiModelsUrl;

  /**
   * Constructs a Chat Completitions object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration Factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger Factory.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory;

    $config_openai = $this->configFactory->get('dropai_openai.settings');
    $this->apiKey = $config_openai->get('api_key') ?: '';
    $this->apiCompletionsUrl = 'https://api.openai.com/v1/chat/completions';
    $this->apiModelsUrl = 'https://api.openai.com/v1/models';
  }

  /**
   * This Function returns available models.
   */
  public function getModels() {
    $output = [];
    $ch = curl_init($this->apiModelsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $this->apiKey
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
      $this->loggerFactory->get('dropai_chatgpt')->error('Request Error: @error', ['@error' => curl_error($ch)]);
    }

    curl_close($ch);
    $json = json_decode($response, TRUE);

    if (isset($json['data'])) {
      foreach ($json['data'] as $model) {
        $output[$model['id']] = $model['id'];
      }
    }
    else {
      $this->loggerFactory->get('dropai_chatgpt')->error('Empty models.');
    }

    return $output;
  }

  /**
   * Request.
   *
   * @param string $model
   * @param array $messages
   */
  public function getChatCompletion(string $model, array $messages) {
    $output = '';
    $data = [
      'model' => $model,
      'messages' => $messages,
    ];

    $ch = curl_init($this->apiCompletionsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $this->apiKey
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
      $this->loggerFactory->get('dropai_chatgpt')->error('Request Error: @error', ['@error' => curl_error($ch)]);
    }

    curl_close($ch);
    $json = json_decode($response, TRUE);

    if (isset($json['choices'][0]['message']['content'])) {
      $output = $json['choices'][0]['message']['content'];
    }

    return $output;
  }

}
