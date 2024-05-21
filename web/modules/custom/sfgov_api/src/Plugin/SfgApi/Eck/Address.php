<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Eck;

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
      'hours' => $this->formatOfficeHours($entity->get('field_operating_hours')->getValue()),
    ];
    return $custom_data;
  }

}
