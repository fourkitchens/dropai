<?php

namespace Drupal\dropai\Plugin\DropaiPreprocessor;

use Drupal\dropai\Plugin\DropaiPreprocessorBase;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Provides a DropAI Preprocessor plugin to TBD.
 *
 * @DropaiPreprocessor(
 *   id = "tbd_python_preprocessor",
 *   label = @Translation("TBD (Python)")
 * )
 */
class TbdPythonPreprocessor extends DropaiPreprocessorBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(string $text): string {
    return parent::preprocess($text);
  }

}
