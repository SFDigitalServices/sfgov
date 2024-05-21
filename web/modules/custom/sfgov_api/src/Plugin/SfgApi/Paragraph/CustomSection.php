<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_custom_section",
 *   title = @Translation("Paragraph custom_section"),
 *   bundle = "custom_section",
 *   wag_bundle = "title_and_text",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class CustomSection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'text' => $entity->get('field_text')->value,
      'title' => $entity->get('field_title')->value,
      'alter' => 'empty_data',
    ];
  }

}
