<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_document",
 *   title = @Translation("Paragraph document"),
 *   bundle = "document",
 *   wag_bundle = "document",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class Document extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'file' => $this->getReferencedEntity($entity->get('field_file')->referencedEntities(), TRUE, TRUE),
      'alter' => 'empty_data',
    ];
  }

}
