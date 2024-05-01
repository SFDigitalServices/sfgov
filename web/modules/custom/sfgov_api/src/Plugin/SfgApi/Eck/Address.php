<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Eck;

use Drupal\office_hours\OfficeHoursDateHelper;
use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "location_physical",
 *   title = @Translation("ECK Location"),
 *   bundle = "physical",
 *   wag_bundle = "Address",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class Address extends SfgApiPluginBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   *
   * Setting entity type here because ECK doesn't have a base entity type.
   */
  protected $entityType = 'location';

  /**
   * {@inheritDoc}
   */
  public function setBaseData($entity) {
    $address_data = $entity->get('field_address');
    $base_data = [
      'line1' => $address_data->address_line1,
      'city' => $address_data->locality,
      'state' => $address_data->administrative_area,
      'zip' => $address_data->postal_code,
    ];
    return $base_data;
  }

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $address_data = $entity->get('field_address');
    $custom_data = [
      'organization' => $address_data->organization,
      'addressee' => $address_data->addressee ?: '',
      'location_name' => $address_data->location_name,
      'line2' => $address_data->address_line2,
      'location_notes' => $entity->get('field_text')->value ?: '',
      'agency' => $this->getReferencedEntity($entity->get('field_department')->referencedEntities(), FALSE, TRUE, TRUE),
      'hours' => $this->formatHours($entity->get('field_operating_hours')->getValue()),
    ];
    return $custom_data;
  }

  /**
   * Format hours data for Wagtail.
   */
  public function formatHours($hours_data) {
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
