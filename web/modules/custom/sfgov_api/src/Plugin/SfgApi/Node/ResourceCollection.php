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
 *   is_stub = {},
 * )
 */
class ResourceCollection extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_description')->value,
      'data_dashboard' => $this->getReferencedData($entity->get('field_data_dashboard')->referencedEntities()),
      'introductory_text' => $this->getReferencedData($entity->get('field_introductory_text')->referencedEntities()),
      // 'documents' => $this->getReferencedData($entity->get('field_paragraphs')->referencedEntities()), // needs to be created in wagtail
      // 'custom_section' => $this->getReferencedData($entity->get('field_content_bottom')->referencedEntities()), // needs to be updated in wagtail
      'related_agencies' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'related_topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),
    ];
  }

}
