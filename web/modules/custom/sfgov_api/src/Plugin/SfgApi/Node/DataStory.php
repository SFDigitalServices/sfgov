<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_data_story",
 *   title = @Translation("Node data_story"),
 *   bundle = "data_story",
 *   wag_bundle = "dataStory",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class DataStory extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_description')->value,
      'content' => $this->getReferencedData($entity->get('field_content')->referencedEntities()),
      'field_departments' => $entity->get('field_departments')->value,
    ];
  }

}
