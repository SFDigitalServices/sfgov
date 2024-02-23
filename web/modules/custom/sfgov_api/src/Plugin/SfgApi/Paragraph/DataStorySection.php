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
 *   referenced_plugins = {
*      "paragraph_callout",
*      "paragraph_image",
*      "paragraph_powerbi_embed",
*      "paragraph_text",
 *   }
 * )
 */
class DataStorySection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $data = [];
    $title = $entity->get('field_title')->value ?: '';
    $section_content = $entity->get('field_content')->referencedEntities() ? $this->getReferencedData($entity->get('field_content')->referencedEntities()): [];
    if (empty($title) && empty($section_content)) {
      $data = [
        'alter' => 'empty_data',
      ];
    }
    else {
      $data = [
        'title' => $title,
        'section_content' => $section_content,
      ];
    }

    return $data;
  }

}
