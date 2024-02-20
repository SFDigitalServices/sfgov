<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_document_subsection",
 *   title = @Translation("Paragraph document_subsection"),
 *   bundle = "document_subsection",
 *   wag_bundle = "document_subsection",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class DocumentSubsection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_title')->value,
      'field_content' => $entity->get('field_content')->value,
    ];
  }

}
