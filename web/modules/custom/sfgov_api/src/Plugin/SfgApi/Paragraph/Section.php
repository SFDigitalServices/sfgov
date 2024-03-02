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
    // @todo , how to handle paragraphs that reference views and blocks?
    // field_news, field_events, field_block
    $data = [];
    $title = $entity->get('field_title')->value ?: '';
    $section_content = $entity->get('field_content')->referencedEntities() ? $this->getReferencedData($entity->get('field_content')->referencedEntities()) : [];
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
