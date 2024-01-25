<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Eck;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "eck_location",
 *   title = @Translation("ECK Location"),
 *   bundle = "location",
 *   wag_bundle = "address",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Address extends SfgApiEckBase {

  /**
   * {@inheritDoc}
   *
   * Setting entity type here because ECK doesn't have a base entity type.
   */
  protected $entityType = 'location';

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $custom_data = [
      'address' => $entity->get('field_address')->getValue(),
      'operating_hours' => $entity->get('field_operating_hours')->getValue(),
      'text' => $entity->get('field_text')->getValue(),
    ];
    return $custom_data;
  }

}
