<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_button",
 *   title = @Translation("Paragraph button"),
 *   bundle = "button",
 *   wag_bundle = "button",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class Button extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'link' => $this->generateLinks($entity->get('field_link')->getvalue()),
    ];
  }

}
