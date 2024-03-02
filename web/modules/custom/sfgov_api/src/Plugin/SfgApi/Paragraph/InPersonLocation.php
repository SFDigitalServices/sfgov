<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_in_person_location",
 *   title = @Translation("Paragraph in_person_location"),
 *   bundle = "in_person_location",
 *   wag_bundle = "in_person_location",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class InPersonLocation extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'address_display' => $this->editFieldValue($entity->get('field_address_display')->value, [1 => TRUE, 0 => FALSE]),
      'location' => $this->getReferencedEntity($entity->get('field_location')->referencedEntities(), TRUE, TRUE),
    ];
  }

}
