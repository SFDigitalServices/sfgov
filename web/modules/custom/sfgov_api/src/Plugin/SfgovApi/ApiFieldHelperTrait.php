<?php

namespace Drupal\sfgov_api\Plugin\SfgovApi;

/**
 * Helper functions for converting field data to a form that wagtail can use.
 */
trait ApiFieldHelperTrait {

  /**
   * Create an array of entity data using the corresponding plugins.
   *
   * @param array $entities
   *   An array of entities.
   * @param bool $reference_only
   *   Whether to return only the reference data or the full entity data.
   *
   * @return array
   *   An array of entity data.
   */
  public function getReferencedData(array $entities, $reference_only = FALSE) {
    $entity_data = [];

    foreach ($entities as $entity) {
      $entity_type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $langcode = $this->configuration['langcode'];
      $entity_data = [];

      if ($reference_only) {
        $entity_data[] = [
          'bundle' => $bundle,
          'id' => $entity->id(),
          'type' => $entity_type,
        ];
      }
      else {
        $sfgov_api_plugin_manager = \Drupal::service('plugin.manager.sfgov_api');
        $available_plugins = $sfgov_api_plugin_manager->getDefinitions();
        switch ($entity_type) {
          case 'paragraph':
            $plugin_label = 'paragraph_' . $bundle;
            break;

          case 'node':
            $plugin_label = 'node_' . $bundle;
            break;

          default:
            $entity_data[] = 'no data found';
            break;
        }

        // Use the established plugin so that the field mappings are consistent.
        if (in_array($plugin_label, array_keys($available_plugins))) {
          $plugin = $sfgov_api_plugin_manager->createInstance($plugin_label, [
            'langcode' => $langcode,
          ]);
          $entity_data[] = $plugin->prepareData();
        }
        else {
          $entity_data[] = 'Error: no available plugins for this entity';
        }
      }
    }
    return $entity_data;
  }

}
