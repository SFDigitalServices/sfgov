<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Eck;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "eck_location_physical",
 *   title = @Translation("ECK Location"),
 *   bundle = "physical",
 *   wag_bundle = "address",
 *   entity_id = {},
 *   langcode = {},
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
  public function setBaseData($eck) {
    $base_data = [];
    return $base_data;
  }

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $custom_data = [
      'title' => $entity->get('title')->value,
      'address' => $entity->get('field_address')->getValue(),
      'operating_hours' => $entity->get('field_operating_hours')->getValue(),
      'description' => $entity->get('field_text')->getValue(),
      'department' => $this->getReferencedEntity($entity->get('field_department')->referencedEntities()),
    ];
    return $custom_data;
  }

}
