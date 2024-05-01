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
 *   is_stub = {},
 *   shape = {},
 * )
 */
class Topic extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_description')->value,
      'top_level_topic' => $this->editFieldValue($entity->get('field_top_level_topic')->value, [1 => TRUE, 0 => FALSE]),
      'content_top' => $this->getReferencedData($entity->get('field_content_top')->referencedEntities()),
      'services' => $this->getReferencedData($entity->get('field_department_services')->referencedEntities()),
      'spotlight' => $this->getReferencedData($entity->get('field_spotlight')->referencedEntities()),
      'content' => $this->getReferencedData($entity->get('field_content')->referencedEntities()),
      'resources' => $this->getReferencedData($entity->get('field_resources')->referencedEntities()),

      // Not sure what these are but wagtail needs them.
      'events' => [],
      'news' => [],
      'information' => [],
      'resource_collections' => [],
      'step_by_steps' => [],
      'topics' => [],
      'transactions' => [],
      'agencies' => [],
    ];
  }

}
