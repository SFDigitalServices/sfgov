<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_agency_section",
 *   title = @Translation("Paragraph agency_section"),
 *   bundle = "agency_section",
 *   wag_bundle = "agency_section",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class AgencySection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_agencies' => $this->getReferencedData($entity->get('field_agencies')->referencedEntities()),
      'title' => $entity->get('field_section_title_list')->value,
    ];
  }

}
