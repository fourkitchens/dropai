<?php

namespace Drupal\dropai\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\media\Entity\Media;
use Symfony\Component\Routing\Route;

class MediaContentAccessCheck implements AccessInterface {

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\media\Entity\Media $media
   *   The media entity.
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Media $media, Route $route) {
    // Allow access when the media is a document.
    if ($media->bundle() === 'document') {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
