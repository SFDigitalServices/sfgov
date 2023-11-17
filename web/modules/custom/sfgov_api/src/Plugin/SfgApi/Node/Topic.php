<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_topic",
 *   title = @Translation("Node topic"),
 *   bundle = "topic",
 *   wag_bundle = "Topic",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Topic extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is incomplete and is only built to demonstrate relationships to other entities
      'top_level_topic' => $entity->get('field_top_level_topic')->value,
      'description' => $entity->get('field_description')->value,
    ];
  }

}
