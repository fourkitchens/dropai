<?php

namespace Drupal\dropai\Plugin\DropaiPreprocessor;

use Drupal\dropai\Plugin\DropaiPreprocessorBase;
use Html2Text\Html2Text;

/**
 * Provides a DropAI Preprocessor plugin to convert HTML to plain text.
 *
 * @DropaiPreprocessor(
 *   id = "plaintext",
 *   label = @Translation("Plain Text")
 * )
 */
class PlainTextPreprocessor extends DropaiPreprocessorBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(string $text): string {
    $html = new Html2Text($text, ['width' => 0]);
    return parent::preprocess($html->getText());
  }

}
