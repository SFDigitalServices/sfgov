<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_help",
 *   title = @Translation("Paragraph help"),
 *   bundle = "help",
 *   wag_bundle = "help",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class Help extends SfgApiParagraphBase {

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
