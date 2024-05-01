<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_special_case",
 *   title = @Translation("Paragraph special_case"),
 *   bundle = "special_case",
 *   wag_bundle = "special_case",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class SpecialCase extends SfgApiParagraphBase {

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
