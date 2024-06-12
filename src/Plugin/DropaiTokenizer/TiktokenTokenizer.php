<?php

namespace Drupal\dropai\Plugin\DropaiTokenizer;

use Drupal\dropai\Plugin\DropaiTokenizerBase;
use Yethee\Tiktoken\EncoderProvider;

/**
 * Provides a DropAI Tokenizer plugin using a PHP Tiktoken library.
 *
 * @DropaiTokenizer(
 *   id = "tiktoken",
 *   label = @Translation("Tiktoken")
 * )
 */
class TiktokenTokenizer extends DropaiTokenizerBase {

  /**
   * Models supported by the tokenizer as Id/name key/value pairs.
   *
   * @var array
   */
  public $models = [
    'gpt-4o' => 'GPT-4 Omni',
    'gpt-4' => 'GPT-4',
    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    'text-embedding-ada-002' => 'Text Embedding Ada-002',
  ];

  /**
   * {@inheritdoc}
   */
  public function tokenize(string $text, string $model): array {
    $provider = new EncoderProvider();
    $encoder = $provider->getForModel($model);
    return $encoder->encode($text);
  }

}
