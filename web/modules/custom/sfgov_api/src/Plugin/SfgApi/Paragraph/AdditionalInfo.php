<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_additional_info",
 *   title = @Translation("Paragraph additional_info"),
 *   bundle = "additional_info",
 *   wag_bundle = "title_and_text",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class AdditionalInfo extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_additional_info_heading')->value,
      'text' => $entity->get('field_additional_info_text')->value,
      'alter' => 'empty_data',
    ];
  }

}
