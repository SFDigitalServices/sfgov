<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_resource_collection",
 *   title = @Translation("Node resource_collection"),
 *   bundle = "resource_collection",
 *   wag_bundle = "ResourceCollection",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {},
 * )
 */
class ResourceCollection extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is incomplete and only exists for entity referencing
      // 'field_content' => $entity->get('field_content')->value,
      // 'field_content_bottom' => $entity->get('field_content_bottom')->value,
      // 'field_data_dashboard' => $entity->get('field_data_dashboard')->value,
      // 'field_departments' => $entity->get('field_departments')->value,
      // 'field_dept' => $entity->get('field_dept')->value,
      // 'field_description' => $entity->get('field_description')->value,
      // 'field_introductory_text' => $entity->get('field_introductory_text')->value,
      // 'field_paragraphs' => $entity->get('field_paragraphs')->value,
      // 'field_sidebar' => $entity->get('field_sidebar')->value,
      // 'field_topics' => $entity->get('field_topics')->value,
    ];
  }

}
