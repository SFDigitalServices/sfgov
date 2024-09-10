<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_data_story_reference_section",
 *   title = @Translation("Paragraph data_story_reference_section"),
 *   bundle = "data_story_reference_section",
 *   wag_bundle = "data_story_reference_section",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class DataStoryReferenceSection extends SfgApiParagraphBase {

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
