<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_data_story_section",
 *   title = @Translation("Paragraph data_story_section"),
 *   bundle = "data_story_section",
 *   wag_bundle = "section",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class DataStorySection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_title')->value ?: 'Section Title',
      'section_content' => $this->getReferencedData($entity->get('field_content')->referencedEntities()),
    ];
  }

}
