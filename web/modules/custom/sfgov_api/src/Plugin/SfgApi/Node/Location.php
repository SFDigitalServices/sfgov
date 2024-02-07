<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_location",
 *   title = @Translation("Node location"),
 *   bundle = "location",
 *   wag_bundle = "LocationPage",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Location extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo finish stubbing out the fields.
      // 'body' => $entity->get('body')->value,
      // 'field_about_description' => $entity->get('field_about_description')->value,
      // 'field_address' => $entity->get('field_address')->value,
      // 'field_alert_expiration_date' => $entity->get('field_alert_expiration_date')->value,
      // 'field_alert_text' => $entity->get('field_alert_text')->value,
      // 'field_at_this_location' => $entity->get('field_at_this_location')->value,
      // 'field_departments' => $entity->get('field_departments')->value,
      // 'field_getting_here_items' => $entity->get('field_getting_here_items')->value,
      // 'field_image' => $entity->get('field_image')->value,
      // 'field_intro_text' => $entity->get('field_intro_text')->value,
      // 'field_locations' => $entity->get('field_locations')->value,
      // 'field_people' => $entity->get('field_people')->value,
      // 'field_phone_numbers' => $entity->get('field_phone_numbers')->value,
      // 'field_services' => $entity->get('field_services')->value,
      // 'field_title' => $entity->get('field_title')->value,
    ];
  }

}
