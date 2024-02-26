<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_information_page",
 *   title = @Translation("Node information_page"),
 *   bundle = "information_page",
 *   wag_bundle = "Information",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {
 *     "node_transaction",
 *     "node_department",
 *     "node_topic",
 *     "node_campaign",
 *     "node_data_story",
 *     "node_information_page",
 *     "node_resource_collection",
 *     "node_step_by_step",
 *     "paragraph_custom_section",
 *     "paragraph_callout",
 *     "paragraph_image",
 *   }
 * )
 */
class InformationPage extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_description')->value ?: '',
      'part_of' => $this->getReferencedEntity($entity->get('field_transactions')->referencedEntities()),
      'partner_agencies' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),
      'related_pages' => $this->getReferencedEntity($entity->get('field_related_content')->referencedEntities(), FALSE, FALSE, TRUE),
      'information_section' => $this->getReferencedData($entity->get('field_information_section')->referencedEntities()),
    ];
  }

}
