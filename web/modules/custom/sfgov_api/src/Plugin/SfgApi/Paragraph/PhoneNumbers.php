<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_phone_numbers",
 *   title = @Translation("Paragraph phone_numbers"),
 *   bundle = "phone_numbers",
 *   wag_bundle = "phone_numbers",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class PhoneNumbers extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'phone_numbers' => $this->getReferencedData($entity->get('field_phone_numbers_phone')->referencedEntities()),
    ];
  }

}
