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
      // @todo this plugin is only fetching data. needs to be massaged.
      'body' => $entity->get('body')->value,
      'field_address' => $this->getReferencedEntity($entity->get('field_address')->referencedEntities()),
      'field_biography' => $entity->get('field_biography')->value,
      'field_city_department' => $this->getReferencedEntity($entity->get('field_city_department')->referencedEntities()),
      'field_direct_external_url' => $this->generateLinks($entity->get('field_direct_external_url')->getvalue()),
      'field_email' => $this->getReferencedData($entity->get('field_email')->referencedEntities()),
      'field_featured_items' => $this->getReferencedData($entity->get('field_featured_items')->referencedEntities()),
      'field_first_name' => $entity->get('field_first_name')->value,
      'field_last_name' => $entity->get('field_last_name')->value,
      'field_phone_numbers' => $this->getReferencedData($entity->get('field_phone_numbers')->referencedEntities()),
      'field_photo' => $this->getReferencedEntity($entity->get('field_photo')->referencedEntities()),
      'field_primary_email' => $entity->get('field_primary_email')->value,
      'field_primary_phone_number' => $entity->get('field_primary_phone_number')->value,
      'field_profile_photo' => $this->getReferencedEntity($entity->get('field_profile_photo')->referencedEntities()),
      'field_profile_positions_held' => $entity->get('field_profile_positions_held')->value,
      'field_profile_type' => $entity->get('field_profile_type')->value,
      'field_pronouns' => $entity->get('field_pronouns')->value,
      'field_social_media' => $this->getReferencedData($entity->get('field_social_media')->referencedEntities()),
      'field_spotlight' => $this->getReferencedData($entity->get('field_spotlight')->referencedEntities()),
      'field_sub_title' => $entity->get('field_sub_title')->value,
      'field_title' => $entity->get('field_title')->value,
    ];
  }

}
