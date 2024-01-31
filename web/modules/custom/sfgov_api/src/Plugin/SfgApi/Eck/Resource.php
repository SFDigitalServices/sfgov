<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Eck;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "eck_resource_resource",
 *   title = @Translation("ECK Location"),
 *   bundle = "resource",
 *   wag_bundle = "resource",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Resource extends SfgApiPluginBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   *
   * Setting entity type here because ECK doesn't have a base entity type.
   */
  protected $entityType = 'resource';

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
      'department' => $this->getReferencedEntity($entity->get('field_department')->referencedEntities()),
      'body' => $entity->get('field_description')->value ?: '',
      'topics' => $this->getReferencedEntity($entity->get('field_topic')->referencedEntities()),
      'url' => $entity->get('field_url')->getValue(),
    ];
    return $custom_data;
  }

}
