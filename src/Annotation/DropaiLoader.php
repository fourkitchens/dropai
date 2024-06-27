<?php

namespace Drupal\dropai\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DropAI Loader item annotation object.
 *
 * @Annotation
 */
class DropaiLoader extends Plugin {

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

  /**
   * The MIME type that this plugin supports (optional).
   *
   * @var string
   */
  public $mime_type;

}
