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
 *   shape = {},
 * )
 */
class Button extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $link_data = $entity->get('field_link')->getvalue();
    $link_label = $link_data ? $link_data[0]['options']['attributes']['aria-label'] : '';

    // This is the shape wagtail expects when the button is empty.
    $button_value = [
      'url' => '',
      'page' => NULL,
      'link_to' => '',
      'link_text' => '',
      'screenreader_label' => '',
    ];
    $button_data = $this->generateLinks($link_data);

    if ($button_data) {
      $button_value = $button_data[0];
      $button_value['value']['screenreader_label'] = $link_label;
    }

    return [
      'alter' => 'flatten_link',
      'link' => $button_value,
    ];
  }

}
