<?php

namespace Drupal\dropai_pipeline_python\Plugin\DropaiTokenizer;

use Drupal\dropai\Plugin\DropaiTokenizerBase;

/**
 * Provides a DropAI Tokenizer plugin using a Python Tiktoken library.
 *
 * @DropaiTokenizer(
 *   id = "tiktoken_python",
 *   label = @Translation("Tiktoken (python)")
 * )
 */
class TiktokenPythonTokenizer extends DropaiTokenizerBase {

  /**
   * Models supported by the tokenizer as Id/name key/value pairs.
   *
   * @var array
   */
  public $models = [
    'tbd' => 'TBD',
  ];

  /**
   * {@inheritdoc}
   */
  public function tokenize(string $text, string $model): array {
    // @todo: Call the webservice to get tokens.
    return [];
  }

}
