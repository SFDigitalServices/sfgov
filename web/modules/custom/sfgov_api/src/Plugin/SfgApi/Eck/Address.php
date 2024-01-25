<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Media;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "locatione",
 *   title = @Translation("ECK Location"),
 *   bundle = "location",
 *   wag_bundle = "address",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Address extends SfgApiMediaBase {

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {

    $custom_data = [];
    return $custom_data;
  }

}
