<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_accordion",
 *   title = @Translation("Paragraph accordion"),
 *   bundle = "accordion",
 *   wag_bundle = "accordion",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {
 *    "paragraph_accordion_item",
 *   }
 * )
 */
class Accordion extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
    // @todo incomplete
      // 'field_content' => $entity->get('field_content')->value,
      // 'field_description' => $entity->get('field_description')->value,
      // 'field_title' => $entity->get('field_title')->value,
    ];
  }

}
