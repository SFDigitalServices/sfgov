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
 * )
 */
class ResourceCollection extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is only fetching data. needs to be massaged.
      'field_content' => $this->getReferencedData($entity->get('field_content')->referencedEntities()),
      'field_content_bottom' => $this->getReferencedData($entity->get('field_content_bottom')->referencedEntities()),
      'field_data_dashboard' => $this->getReferencedData($entity->get('field_data_dashboard')->referencedEntities()),
      'field_departments' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'field_description' => $entity->get('field_description')->value,
      'field_introductory_text' => $entity->get('field_introductory_text')->value,
      'field_paragraphs' => $this->getReferencedData($entity->get('field_paragraphs')->referencedEntities()),
      'field_sidebar' => $this->getReferencedData($entity->get('field_sidebar')->referencedEntities()),
      'topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),
    ];
  }

}
