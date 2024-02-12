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
      'top_level_topic' => $entity->get('field_top_level_topic')->value,
      'description' => $entity->get('field_description')->value,

      // 'field_content' => $entity->get('field_content')->value,
      // 'field_content_top' => $entity->get('field_content_top')->value,
      // 'field_departments' => $entity->get('field_departments')->value,
      // 'field_department_services' => $entity->get('field_department_services')->value,
      // 'field_description' => $entity->get('field_description')->value,
      // 'field_page_design' => $entity->get('field_page_design')->value,
      // 'field_resources' => $entity->get('field_resources')->value,
      // 'field_spotlight' => $entity->get('field_spotlight')->value,
      // 'field_topics' => $entity->get('field_topics')->value,
      // 'field_top_level_topic' => $entity->get('field_top_level_topic')->value,
    ];
  }

}
