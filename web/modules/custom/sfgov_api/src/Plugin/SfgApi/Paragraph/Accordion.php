<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_accordion",
 *   title = @Translation("Paragraph accordion"),
 *   bundle = "accordion",
 *   wag_bundle = "accordion",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class Accordion extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_content' => $this->getReferencedData($entity->get('field_content')->referencedEntities()),
      'field_description' => $entity->get('field_description')->value,
      'field_title' => $entity->get('field_title')->value,
    ];
  }

}
