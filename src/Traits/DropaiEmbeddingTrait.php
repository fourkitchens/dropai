<?php

namespace Drupal\dropai\Traits;

/**
 * Provides methods to assist embeembeddeddding entities.
 */
trait DropaiEmbeddingTrait {

  /**
   * This function checks if the entity allows indexing.
   */
  function dropai_check_entity_allows_indexing(string $entity_type, string $entity_bundle) {
    $dropai_indexing_settings = \Drupal::service('config.factory')->get('dropai.indexing_settings');
    if (is_null($dropai_indexing_settings)) {
      // The settings are not defined yet.
      return FALSE;
    }

    $dropai_indexing_settings = $dropai_indexing_settings->getRawData();
    if (empty($dropai_indexing_settings) || !isset($dropai_indexing_settings['entities'])) {
      // The entities settings is empty.
      return FALSE;
    }

    $indexing_entities = $dropai_indexing_settings['entities'];
    if (!array_key_exists($entity_type, $indexing_entities)) {
      // The entity type is outside the global entities settings.
      return FALSE;
    }

    $entiy_settings = $indexing_entities[$entity_type];
    if ($entiy_settings['index_type'] === 'exclude') {
      if (in_array($entity_bundle, $entiy_settings['bundles'])) {
        // It is FALSE because the bundle is one of the excluded items.
        return FALSE;
      }
      else {
        // It is TRUE because the bundle is NOT one of the excluded items.
        return TRUE;
      }
    }
    elseif ($entiy_settings['index_type'] === 'include') {
      if (in_array($entity_bundle, $entiy_settings['bundles'])) {
        // It is TRUE because the bundle is one of the included items.
        return TRUE;
      }
      else {
        // It is FALSE because the bundle is not one of the included items.
        return FALSE;
      }
    }

    // The index_type is unknown.
    return FALSE;
  }

}
