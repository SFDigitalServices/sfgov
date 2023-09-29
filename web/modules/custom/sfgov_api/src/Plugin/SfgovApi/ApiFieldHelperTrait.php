<?php

namespace Drupal\sfgov_api\Plugin\SfgovApi;

use Drupal\Core\Datetime\DrupalDateTime;

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
    $entities_data = [];

    foreach ($entities as $entity) {
      $entity_type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $langcode = $this->configuration['langcode'];

      if ($reference_only) {
        $entities_data[] = [
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

          case 'media':
            $plugin_label = 'media_' . $bundle;
            break;

          default:
            $entities_data[] = 'no data found';
            break;
        }

        // Use the established plugin so that the field mappings are consistent.
        if (in_array($plugin_label, array_keys($available_plugins))) {
          $plugin = $sfgov_api_plugin_manager->createInstance($plugin_label, [
            'langcode' => $langcode,
          ]);
          $entities_data = array_merge($entities_data, $plugin->prepareData([$entity]));
        }
        else {
          $entities_data[] = 'Error: no available plugins for this entity';
        }
      }
    }

    return $entities_data;
  }

  /**
   * Return time in the format that wagtail expects.
   *
   * @param int $timestamp
   *   A unix timestamp.
   *
   * @return string
   *   A string in the format that wagtail expects.
   */
  public function getWagtailTime($timestamp) {
    $drupal_datetime = DrupalDateTime::createFromTimestamp($timestamp);
    return $drupal_datetime->format('Y-m-d\TH:i:s.uP');
  }

}
