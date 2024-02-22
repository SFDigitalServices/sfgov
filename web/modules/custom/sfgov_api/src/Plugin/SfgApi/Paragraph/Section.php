<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_section",
 *   title = @Translation("Paragraph section"),
 *   bundle = "section",
 *   wag_bundle = "section",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Section extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_title')->value ?? '',
      'section_content' => $this->getReferencedData($entity->get('field_content')->referencedEntities()),
    ];
  }

}
