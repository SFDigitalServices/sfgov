<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Eck;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "location_event_address",
 *   title = @Translation("ECK Location"),
 *   bundle = "event_address",
 *   wag_bundle = "Address",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class EventAddress extends SfgApiPluginBase {

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
  public function setBaseData($eck) {
    $base_data = [];
    return $base_data;
  }

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $address_data = $entity->get('field_address');
    $custom_data = [
      'location_name' => $entity->get('title')->value,
      'line1' => $address_data->address_line1,
      'line2' => $address_data->address_line2,
      'city' => $address_data->locality,
      'state' => $address_data->administrative_area,
      'zip' => $address_data->postal_code,
    ];
    return $custom_data;
  }

}
