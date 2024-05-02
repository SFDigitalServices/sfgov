<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_accordion_item",
 *   title = @Translation("Paragraph accordion_item"),
 *   bundle = "accordion_item",
 *   wag_bundle = "title_and_text",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class AccordionItem extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_content' => $entity->get('field_content')->value,
      'field_title' => $entity->get('field_title')->value,
    ];
  }

}
