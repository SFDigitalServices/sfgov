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
 *   shape = {},
 * )
 */
class Button extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // This is the shape wagtail expects when the button is empty.
    $empty_button = [
      'url' => '',
      'page' => NULL,
      'link_to' => '',
      'link_text' => '',
    ];
    $button_data = $this->generateLinks($entity->get('field_link')->getvalue());
    $button_value = $button_data ? $button_data[0] : $empty_button;
    return [
      'alter' => 'flatten_link',
      'link' => $button_value,
    ];
  }

}
