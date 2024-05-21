<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_document_section",
 *   title = @Translation("Paragraph document_section"),
 *   bundle = "document_section",
 *   wag_bundle = "document_section",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class DocumentSection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'content' => $this->getReferencedData($entity->get('field_content')->referencedEntities()),
    ];
  }

}
