<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Eck;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

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
  public function setCustomData($entity) {
    $custom_data = [
      'title' => $entity->get('title')->value,
      'address' => $entity->get('field_address')->getValue(),
      'operating_hours' => $entity->get('field_operating_hours')->getValue(),
      'description' => $entity->get('field_text')->getValue(),
      'department' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
    ];
    return $custom_data;
  }

}
