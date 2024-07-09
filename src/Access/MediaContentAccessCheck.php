<?php

namespace Drupal\dropai\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\Routing\Route;

/**
 * Checks access when displaying media content.
 */
class MediaContentAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    // @todo: Implement applies() method.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {
    // @todo: Implement access() method.
    return AccessResult::allowed();
  }

}
