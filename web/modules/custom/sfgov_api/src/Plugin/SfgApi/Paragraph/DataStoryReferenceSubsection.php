<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_data_story_reference_subsection",
 *   title = @Translation("Paragraph data_story_reference_subsection"),
 *   bundle = "data_story_reference_subsection",
 *   wag_bundle = "data_story_reference_subsection",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class DataStoryReferenceSubSection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'resources' => $this->getReferencedData($entity->get('field_data_story')->referencedEntities()),
      'title' => $entity->get('field_title')->value,
    ];
  }

}
