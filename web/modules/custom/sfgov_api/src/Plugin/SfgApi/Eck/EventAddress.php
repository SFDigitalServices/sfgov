<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Eck;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "eck_location_event_address",
 *   title = @Translation("ECK Location"),
 *   bundle = "event_address",
 *   wag_bundle = "address",
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
    $custom_data = [
      'address' => $entity->get('field_address')->getValue(),
    ];
    return $custom_data;
  }

}
