<?php

namespace Drupal\dropai\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a controller for the DropAI module admin pages.
 */
class AdminController extends ControllerBase {

  /**
   * Returns a simple text response for the AI Engine admin page.
   *
   * @return array
   *   A renderable array containing the text response.
   */
  public function content() {
    return [
      '#markup' => $this->t('Admin DropAI settings.'),
    ];
  }

}
