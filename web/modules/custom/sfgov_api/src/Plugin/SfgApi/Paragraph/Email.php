<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_email",
 *   title = @Translation("Paragraph email"),
 *   bundle = "email",
 *   wag_bundle = "email",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Email extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'email' => $entity->get('field_email')->value,
      'title' => $entity->get('field_title')->value,
    ];
  }

}
