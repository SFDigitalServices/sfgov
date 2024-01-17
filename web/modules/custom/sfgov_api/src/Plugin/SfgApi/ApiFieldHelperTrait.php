<?php

namespace Drupal\sfgov_api\Plugin\SfgApi;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Helper functions for converting field data to a form that wagtail can use.
 */
trait ApiFieldHelperTrait {

  /**
   * Get data from an entity using the corresponding plugins.
   *
   * @param array $entities
   *   An array of entities.
   *
   * @return array
   *   An array of entity data.
   */
  public function getReferencedData(array $entities) {
    $sfgov_api_plugin_manager = \Drupal::service('plugin.manager.sfgov_api');
    $available_plugins = $sfgov_api_plugin_manager->getDefinitions();

    $entities_data = [];
    foreach ($entities as $entity) {
      $entity_type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $langcode = $this->configuration['langcode'];

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
          'entity_id' => $entity->id(),
        ]);
        $entities_data[] = [
          'type' => $plugin->pluginDefinition['wag_bundle'],
          'value' => $plugin->getPayload()->getPayloadData(),
        ];
      }
      else {
        $entities_data[] = 'Error: no available plugins for this entity';
      }
    }
    return $entities_data;
  }

  /**
   * Get entity reference data using the corresponding plugins.
   *
   * @param array $entities
   *   An array of entities.
   * @param bool $id_only
   *  Whether to return only the id of the referenced entity.
   * @param bool $flatten
   *  Whether to flatten the array into a single entry.
   *
   * @return array
   *   An array of entity references.
   */
  public function getReferencedEntity(array $entities, $id_only = FALSE, $flatten = FALSE) {
    $wagtail_utilities = \Drupal::service('sfgov_api.utilities');
    $entities_data = [];
    foreach ($entities as $entity) {
      $entity_id = $entity->id();
      $entity_type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $langcode = $entity->language()->getId();
      $wagtail_id = $wagtail_utilities->getWagtailId($entity_id, $entity_type, $langcode) ?: 'not found';
      $reference_data = [];

      switch ($entity_type) {
        case 'paragraph':
          // Paragraphs become streamfields in wagtail. Which expect a type and
          // a value. Other data is just metadata.
          $reference_data['drupal_id'] = (int) $entity_id;
          $reference_data['entity_type'] = $entity_type;
          $reference_data['type'] = $bundle;
          $reference_data['value'] = (int) $wagtail_id;
          break;

        case 'node':
          // Node references are made via their Wagtail ID and bundle.
          if ($id_only) {
            $reference_data = $wagtail_id;
          }
          else {
            $reference_data = '/api/cms/sf.' . $wagtail_utilities->getWagBundle($entity) . '/' . $wagtail_id;
          }
          break;

        case 'media':
          // Media references are very similar to node references.
          if ($id_only) {
            $reference_data = $wagtail_id;
          }
          else {
            $reference_data = $wagtail_utilities->getCredentials()['api_url_base'] . $wagtail_utilities->getWagBundle($entity) . '/' . $wagtail_id;
          }
          break;
      }
      $entities_data[] = $reference_data;
    }

    if ($flatten) {
      $entities_data = $entities_data[0];
    }

    return $entities_data;
  }

  /**
   * Take drupal data and combine it into a streamfield for Wagtail.
   *
   * @param array $data
   *   An array of data.
   *
   * @return array
   *   An array in the expected streamfield shape.
   */
  public function setToStreamField($data, $streamfield_type) {
    $streamfield_array = [
      'type' => $streamfield_type,
      'value' => $data,
    ];
    return $streamfield_array;
  }

  /**
   * Convert a timestamp to a specific date format.
   *
   * @param int $timestamp
   *   The timestamp to convert.
   * @param string $format
   *   The format to convert to.
   *
   * @return string
   *   The converted date.
   */
  public function convertTimestampToFormat(int $timestamp, string $format) {
    $drupal_datetime = DrupalDateTime::createFromTimestamp($timestamp);
    return $drupal_datetime->format($format);
  }

  /**
   * Convert a date from one format to another.
   *
   * @param string $input_format
   *   The input format.
   * @param string $value
   *   The value to convert.
   *
   * @return string
   *   The converted date in format 'YYYY-MM-DD'.
   */
  public function convertDateFromFormat(string $input_format, string $value, $output_format) {
    $drupalDatetime = DrupalDateTime::createFromFormat($input_format, $value);
    return $drupalDatetime->format($output_format);
  }

  /**
   * Edit a field value based on a provided map.
   *
   * @param string $value
   *   The value from drupal to edit.
   * @param array $value_map
   *   An array of values to map drupal_value => wagtail_value.
   *
   * @return string
   *   The edited value that wagtail expects.
   */
  public function editFieldValue($value, $value_map) {
    if (array_key_exists($value, $value_map)) {
      return $value_map[$value];
    }
    else {
      return $value;
    }
  }

}
