<?php

namespace Drupal\sfgov_api\Plugin\SfgApi;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Helper functions for converting field data to a form that wagtail can use.
 */
trait ApiFieldHelperTrait {

  /**
   * Get data from an entity using the corresponding plugins.
   *
   * @param array $entities
   *   An array of entities.
   * @param string $type
   *   The type of entity, only used for changing the plugin output for wagtail.
   *
   * @return array
   *   An array of entity data.
   */
  public function getReferencedData(array $entities, $type = '', $shape = 'wag') {
    $sfgov_api_plugin_manager = \Drupal::service('plugin.manager.sfgov_api');
    $available_plugins = $sfgov_api_plugin_manager->getDefinitions();

    $entities_data = [];
    foreach ($entities as $entity) {
      $entity_type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      if ($shape === 'wag') {
        $langcode = $this->configuration['langcode'];
      }
      elseif ($shape === 'raw') {
        $langcode = $this->requestedLangcode;
      }
      $plugin_label = $entity_type . '_' . $bundle;

      // Use the established plugin so that the field mappings are consistent.
      if (in_array($plugin_label, array_keys($available_plugins))) {
        $plugin = $sfgov_api_plugin_manager->createInstance($plugin_label, [
          'langcode' => $langcode,
          'entity_id' => $entity->id(),
          'shape' => $shape,
        ]);
        if ($shape === 'wag') {
          $entities_data[] = [
            'type' => $type ?: $plugin->getWagBundle(),
            'value' => $plugin->getPayload()->getPayloadData(),
          ];
        }
        elseif ($shape === 'raw') {
          $entities_data[] = [
            'type' => $type ?: $plugin->getBundle(),
            'value' => $plugin->getPayload()->getPayloadData(),
          ];
        }
      }
      else {
        $entities_data[] = 'Error: no available plugins for bundle ' . $bundle . ' of type ' . $entity_type;
      }
    }

    // Handle reference-specific errors and changes.
    foreach ($entities_data as $key => $entity_data) {
      if (is_array($entity_data)) {
        if (isset($entity_data['value']['alter'])) {
          switch ($entity_data['value']['alter']) {
            // Flatten the data.
            case 'flatten':
              $entities_data[$key] = [
                'type' => $type ?: $entity_data['type'],
                'value' => $entity_data['value']['value'],
              ];
              break;

            case 'flatten_link':
              $entities_data[$key] = $entity_data['value']['link']['value'];
              break;

            // Remove empty data.
            case 'empty_data':
              if (isset($entity_data['value']['value'])) {
                if (empty($entity_data['value']['value'])) {
                  unset($entities_data[$key]);
                }
              }
              if (isset($entity_data['value']['file'])) {
                if (empty($entity_data['value']['file'])) {
                  unset($entities_data[$key]);
                }
              }
              break;
          }
        }
      }
    }
    // re-index the array in case anything got removed.
    $entities_data = array_values($entities_data);
    return $entities_data;
  }

  /**
   * Get references to entities using the corresponding plugins.
   *
   * @param array $entities
   *   An array of entities.
   * @param bool $id_only
   *   Whether to return only the id of the referenced entity.
   * @param bool $flatten
   *   Whether to flatten the array into a single entry.
   * @param bool $multitype
   *   Whether the referenced entity can be any type.
   * @param array $extra
   *   Extra data to include in the reference.
   *
   * @return array
   *   An array of entity references.
   */
  public function getReferencedEntity(array $entities, $id_only = FALSE, $flatten = FALSE, $multitype = FALSE, $extra = []) {
    $wagtail_utilities = \Drupal::service('sfgov_api.utilities');
    $entities_data = [];
    if (empty($entities)) {
      return $entities_data;
    }
    foreach ($entities as $entity) {
      $entity_id = $entity->id();
      $entity_type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $langcode = $entity->language()->getId();
      $wagtail_id = $wagtail_utilities->getWagtailId($entity_id, $entity_type, $bundle, $langcode) ?: NULL;
      $reference_data = [];

      if (!$wagtail_id) {
        $reference_data = [
          'empty_reference' => TRUE,
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'langcode' => $langcode,
          'entity_id' => $entity_id,
        ];
      }
      elseif ($id_only) {
        $reference_data = (int) $wagtail_id;
      }
      else {
        // This is an awkward point to insert the multitype logic but it works.
        $entity_type = $multitype ? 'multitype' : $entity_type;
        switch ($entity_type) {
          case 'paragraph':
            // Paragraphs become streamfields in wagtail. Which expect a type
            // and a value. Other data is just metadata.
            $reference_data['drupal_id'] = (int) $entity_id;
            $reference_data['entity_type'] = $entity_type;
            $reference_data['type'] = $bundle;
            $reference_data['value'] = (int) $wagtail_id;
            break;

          case 'node':
            // Node references are made via their Wagtail ID and bundle.
            $reference_data = '/api/cms/sf.' . $wagtail_utilities->getWagBundle($entity) . '/' . $wagtail_id;
            break;

          case 'media':
            // Media references are very similar to node references.
            $reference_data = $wagtail_utilities->getCredentials()['api_url_base'] . $wagtail_utilities->getWagBundle($entity) . '/' . $wagtail_id;
            break;

          case 'multitype':
            // Reference link is slightly different when the entity can be any
            // type.
            $reference_data = $wagtail_utilities->getCredentials()['api_url_base'] . 'pages' . '/' . $wagtail_id;
            break;

          case 'location':
            // Location references are very similar to node references.
            $reference_data = $wagtail_utilities->getCredentials()['api_url_base'] . 'cms.Address' . '/' . $wagtail_id;
            break;
        }
        if (!empty($extra)) {
          $reference_data = array_merge($reference_data, $extra);
        }
      }
      $entities_data[] = $reference_data;
    }

    // Generally if there is only a single reference we want to flatten the
    // array into a single entry, otherwise wagtail errors out.
    if ($flatten) {
      if (isset($entities_data[0])) {
        $entities_data = $entities_data[0];
      }
      else {
        $entities_data = '';
      }
    }

    return $entities_data;
  }

  /**
   * Take drupal data and combine it into a streamfield for Wagtail.
   *
   * @param array $data
   *   An array of data.
   * @param string $streamfield_type
   *   The type of streamfield to create.
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
   * @param string $output_format
   *   The output format.
   *
   * @return string
   *   The converted date in the requested format.
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

  /**
   * Convert the smart date field to different format.
   *
   * @param array $data
   *   The smart date field data.
   *
   * @return array
   *   The converted data.
   */
  public function convertSmartDate($data) {
    if ($data['value'] === NULL) {
      return [];
    }
    // Same logic thats used in SfgovDateFormatterBase.
    $start_date = $this->convertTimestampToFormat($data['value'], 'Y-m-d');
    $start_time = $this->convertTimestampToFormat($data['value'], 'H:i:s');
    $is_all_day = FALSE;
    $include_end_date_time = TRUE;
    $end_date = $this->convertTimestampToFormat($data['end_value'], 'Y-m-d');
    $end_time = $this->convertTimestampToFormat($data['end_value'], 'H:i:s');

    if ($start_time != $end_time) {
      // If you mark it as "all day" the smart_date saves the time values as
      // 11:59pm - 12:00am.
      if ($start_time === '00:00:00' && $end_time === '23:59:00') {
        $is_all_day = TRUE;
      }
      // If the end time is '11:59' on the day of the start time,
      // hide it from display. This is how editors
      // can indicate that there is no end time.
      if ($end_time === '23:59:00') {
        if ($start_date == $end_date) {
          $include_end_date_time = FALSE;
        }
      }
    }

    $data = [
      'end_date' => $end_date,
      'end_time' => $end_time,
      'is_all_day' => $is_all_day,
      'start_date' => $start_date,
      'start_time' => $start_time,
      // @todo , change this back to a boolean once its fixed on the wagtail side.
      'include_end_date_time' => $include_end_date_time ? 'yes' : 'no',
    ];
    return $data;
  }

  /**
   * Generate links in the shape wagtail expects.
   */
  public function generateLinks(array $links_data) {
    $links = [];
    if (empty($links_data)) {
      return $links;
    }
    foreach ($links_data as $link) {
      $url = Url::fromUri($link['uri']);
      $is_external = UrlHelper::isExternal($url->toString());
      if (!$is_external) {
        $entityTypeManager = \Drupal::entityTypeManager();
        if ($url->isRouted()) {
          $url_elements = explode('/', $url->getInternalPath());
          $nid = $url_elements[1];
          if ($nid) {
            $node = $entityTypeManager->getStorage('node')->load($nid);
            $wagtail_id = isset($node) ? $this->getReferencedEntity([$node], TRUE, TRUE) : "Error: no wagtail id found for node:" . $nid;
          }
        }
        else {
          // @todo this is a temp fix. /department/3194 has a 'featured_item'
          // that leads to the following path which is "internal" but not up
          // to date and can't find the right path.
          // https://sf.gov/departments/small-business-commission
          $wagtail_id = "Error: no wagtail id found for unrouted link: " . $url->toString();
        }
      }

      $links[] = [
        'type' => 'page',
        'value' => [
          'url' => $is_external ? $link['uri'] : '',
          'page' => $is_external ? NULL : $wagtail_id,
          'link_to' => $is_external ? 'url' : 'page',
          'link_text' => $link['title'],
        ],
      ];
    }
    return $links;
  }

  /**
   *
   */
  public function getRawImage($image_field) {
    if (empty($image_field)) {
      return [];
    }
    $image_data = $image_field[0];
    $file_path = $image_data->entity->getFileUri();
    if (!$file_path) {
      $message = $this->t('No base file found');
      $this->addPluginError('No file', $message);
    }
    else {
      $file_data = [
        'title' => $image_data->entity->get('filename')->value,
        'file' => $file_path,
        'fid' => $image_data->entity->id(),
        // @todo , remove once we have a better source for alt text.
        'alt_text' => $image_data->get('field_logo')[0]->get('alt')->getValue() ?: '',
      ];
    }
    return $file_data;
  }

  /**
   * Format hours data for Wagtail.
   */
  public function formatOfficeHours($hours_data) {
    if (empty($hours_data)) {
      return [];
    }
    // Set starter variables.
    $hours_type = 'custom_hours';
    $days_map = [
      0 => 'sunday',
      1 => 'monday',
      2 => 'tuesday',
      3 => 'wednesday',
      4 => 'thursday',
      5 => 'friday',
      6 => 'saturday',
    ];

    // Gather some basic info.
    $listed_days = [];
    $start_times = [];
    $end_times = [];
    $day_values = [];
    foreach ($hours_data as $day) {
      $listed_days[] = $day['day'];
      // Since the office_hours module does weird things to store the time,
      // we need to use it to normalize the time.
      // @see OfficeHoursDateHelper::format()
      $start_times[] = OfficeHoursDateHelper::format($day['starthours'], 'H:i:s');
      $end_times[] = OfficeHoursDateHelper::format($day['endhours'], 'H:i:s');
      $day_values[$days_map[$day['day']]] = [
        'open' => OfficeHoursDateHelper::format($day['starthours'], 'H:i:s'),
        'closed' => OfficeHoursDateHelper::format($day['endhours'], 'H:i:s'),
        'break_hours' => [],
      ];
    }

    // Check if Monday-Friday.
    sort($listed_days);
    if ($listed_days == [1, 2, 3, 4, 5]) {
      if (count(array_unique($start_times)) == 1 && count(array_unique($end_times))) {
        $hours_type = 'set_hours';
      }
    }

    sort($start_times);
    sort($end_times);
    $start_time = reset($start_times);
    $end_time = reset($end_times);
    // Build the final array.
    $formatted_hours_data = ['type' => 'office_hours'];
    $formatted_hours_data['value']['all'] = [
      'open' => $start_time,
      'closed' => $end_time,
      'break_hours' => [],
    ];
    $formatted_hours_data['value']['days'] = $hours_type;
    foreach ($day_values as $day => $hours) {
      $formatted_hours_data['value'][$day] = $hours;
    }

    return [$formatted_hours_data];
  }

}
