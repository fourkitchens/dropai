<?php

namespace Drupal\dropai\Plugin\DropaiStorage;

use Drupal\dropai\Plugin\DropaiStorageBase;

/**
 * Provides a DropAI Storage plugin no not doing anything.
 *
 * @DropaiStorage(
 *   id = "none",
 *   label = @Translation("No Storage")
 * )
 */
class NoStorage extends DropaiStorageBase {

}
