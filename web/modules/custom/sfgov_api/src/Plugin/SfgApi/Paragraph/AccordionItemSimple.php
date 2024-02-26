<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_accordion_item_simple",
 *   title = @Translation("Paragraph accordion_item_simple"),
 *   bundle = "accordion_item_simple",
 *   wag_bundle = "title_and_text",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {},
 * )
 */
class AccordionItemSimple extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'text' => $entity->get('field_text')->value,
      'title' => $entity->get('field_title')->value,
    ];
  }

}
