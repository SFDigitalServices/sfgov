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
 *   wag_bundle = "DataStory",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class DataStory extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_description')->value ?: '',
      'content' => $this->getReferencedData($entity->get('field_content')->referencedEntities()),
      'partner_agencies' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
    ];
  }

}
