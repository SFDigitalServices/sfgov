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
 * )
 */
class InformationPage extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $data = [
      'description' => $entity->get('field_description')->value ?: '',
      'part_of' => $this->getReferencedEntity($entity->get('field_transactions')->referencedEntities()),
      'partner_agencies' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),
      'related_pages' => $this->getReferencedEntity($entity->get('field_related_content')->referencedEntities(), FALSE, FALSE, TRUE),
    ];

    // Workaround to remove the image section if the image is not set.
    $information_section = [];
    foreach ($this->getReferencedData($entity->get('field_information_section')->referencedEntities()) as $section) {
      if ($section['type'] == 'image' && $section['value'] == 0) {
        continue;
      }
      $information_section[] = [
        'type' => $section['type'],
        'value' => $section['value'],
      ];
    }

    $data['information_section'] = $information_section;
    return $data;
  }

}
