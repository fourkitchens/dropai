<?php

namespace Drupal\dropai\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DropAI Splitter item annotation object.
 *
 * @Annotation
 */
class DropaiSplitter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
