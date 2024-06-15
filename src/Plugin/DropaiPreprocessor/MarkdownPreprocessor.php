<?php

namespace Drupal\dropai\Plugin\DropaiPreprocessor;

use Drupal\dropai\Plugin\DropaiPreprocessorBase;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Provides a DropAI Preprocessor plugin to convert HTML to markdown.
 *
 * @DropaiPreprocessor(
 *   id = "markdown",
 *   label = @Translation("Markdown")
 * )
 */
class MarkdownPreprocessor extends DropaiPreprocessorBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(string $text): string {
    $converter = new HtmlConverter(['strip_tags' => true]);
    return parent::preprocess($converter->convert($text));
  }

}
