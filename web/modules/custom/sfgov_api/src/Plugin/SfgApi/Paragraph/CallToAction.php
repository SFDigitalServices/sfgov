<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_call_to_action",
 *   title = @Translation("Paragraph call_to_action"),
 *   bundle = "call_to_action",
 *   wag_bundle = "button",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class CallToAction extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // This is referencing a button paragraph which can only ever have one
    // value so its safe to hardcode the 0 index. Call to action already
    // adds the button label, so we're just returning the value from the
    // plugin.
    $button_paragraph = $this->getReferencedData($entity->get('field_button')->referencedEntities())[0];
    return [
      'link' => $button_paragraph,
      'title' => $entity->get('field_title')->value ?: '',
    ];
  }

}
