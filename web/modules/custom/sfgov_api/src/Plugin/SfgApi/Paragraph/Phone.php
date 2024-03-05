<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_phone",
 *   title = @Translation("Paragraph phone"),
 *   bundle = "phone",
 *   wag_bundle = "phone_number",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class Phone extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'owner' => $entity->get('field_owner')->value,
      'details' => $entity->get('field_text')->value,
      'phone_number' => $entity->get('field_tel')->value,
    ];
  }

}
