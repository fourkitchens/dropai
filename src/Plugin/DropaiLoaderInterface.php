<?php

namespace Drupal\dropai\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for DropAI Loader plugins.
 */
interface DropaiLoaderInterface extends PluginInspectionInterface {

  /**
   * Load a Drupal entity and represent it as a string.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A Drupal entity to load as a string.
   *
   * @return string
   *   A text-representation of the entity.
   */
  public function load(ContentEntityInterface $entity): string;

}
