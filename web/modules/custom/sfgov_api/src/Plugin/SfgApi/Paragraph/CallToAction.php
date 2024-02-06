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
 * )
 */
class CallToAction extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // By default the getReferencedData method will return the data wrapped
      // in an array. this works most of the time, but breaks when wagtail
      // has a multivalue field. Remove the outer array to make it work.
      'link' => $this->getReferencedData($entity->get('field_button')->referencedEntities())[0],
      'title' => $entity->get('field_title')->value,
    ];
  }

}
