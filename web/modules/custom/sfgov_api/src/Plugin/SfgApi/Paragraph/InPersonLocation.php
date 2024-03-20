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
 *   wag_bundle = "address",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class InPersonLocation extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'alter' => 'flatten',
      'value' => $this->getReferencedEntity($entity->get('field_location')->referencedEntities(), TRUE, TRUE),
    ];
  }

}
