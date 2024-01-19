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
 *   wag_bundle = "information_page",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class InformationPage extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_description')->value,
      'part_of' => $this->getReferencedEntity($entity->get('field_transactions')->referencedEntities()),
      'information_section' => $this->getReferencedData($entity->get('field_information_section')->referencedEntities()),
      'partner_agencies' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),
      // // @todo, this field references a bunch of content types that don't exist in the API yet.
      'related_pages' => $this->getReferencedEntity($entity->get('field_related_content')->referencedEntities()),
    ];
  }

}
