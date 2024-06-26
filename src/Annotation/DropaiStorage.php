<?php

namespace Drupal\dropai\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DropAI Storage item annotation object.
 *
 * @Annotation
 */
class DropaiStorage extends Plugin {

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
