<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_text",
 *   title = @Translation("Paragraph text"),
 *   bundle = "text",
 *   wag_bundle = "text",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {},
 * )
 */
class Text extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'text' => $entity->get('field_text')->value,
    ];
  }

}
