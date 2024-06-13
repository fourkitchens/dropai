<?php

namespace Drupal\dropai\Plugin\DropaiSplitter;

use Drupal\dropai\Plugin\DropaiSplitterBase;

/**
 * Provides a DropAI Splitter plugin for TBD.
 *
 * @DropaiSplitter(
 *   id = "tbd_python_splitter",
 *   label = @Translation("TBD (Python)")
 * )
 */
class TbdPythonSplitter extends DropaiSplitterBase {

  /**
   * {@inheritdoc}
   */
  public function split(string $text, ...$args): array {
    return [$text];
  }

}
