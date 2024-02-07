<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_person",
 *   title = @Translation("Node person"),
 *   bundle = "person",
 *   wag_bundle = "person",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Person extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo finish stubbing out the fields.
      // 'body' => $entity->get('body')->value,
      // 'field_address' => $entity->get('field_address')->value,
      // 'field_biography' => $entity->get('field_biography')->value,
      // 'field_city_department' => $entity->get('field_city_department')->value,
      // 'field_direct_external_url' => $entity->get('field_direct_external_url')->value,
      // 'field_email' => $entity->get('field_email')->value,
      // 'field_featured_items' => $entity->get('field_featured_items')->value,
      // 'field_first_name' => $entity->get('field_first_name')->value,
      // 'field_last_name' => $entity->get('field_last_name')->value,
      // 'field_phone_numbers' => $entity->get('field_phone_numbers')->value,
      // 'field_photo' => $entity->get('field_photo')->value,
      // 'field_primary_email' => $entity->get('field_primary_email')->value,
      // 'field_primary_phone_number' => $entity->get('field_primary_phone_number')->value,
      // 'field_profile_photo' => $entity->get('field_profile_photo')->value,
      // 'field_profile_positions_held' => $entity->get('field_profile_positions_held')->value,
      // 'field_profile_type' => $entity->get('field_profile_type')->value,
      // 'field_pronouns' => $entity->get('field_pronouns')->value,
      // 'field_social_media' => $entity->get('field_social_media')->value,
      // 'field_spotlight' => $entity->get('field_spotlight')->value,
      // 'field_sub_title' => $entity->get('field_sub_title')->value,
      // 'field_title' => $entity->get('field_title')->value,
    ];
  }

}
