<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_about",
 *   title = @Translation("Node about"),
 *   bundle = "about",
 *   wag_bundle = "About",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class About extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_about_resources' => $this->getReferencedData($entity->get('field_about_resources')->referencedEntities()),
      'field_custom_sections' => $this->getReferencedData($entity->get('field_custom_sections')->referencedEntities()),
      'field_parent_department' => $this->getReferencedEntity($entity->get('field_parent_department')->referencedEntities()),
    ];
  }

}
