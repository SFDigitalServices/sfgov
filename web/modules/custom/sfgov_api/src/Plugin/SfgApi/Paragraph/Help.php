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
 *   wag_bundle = "title_and_text",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
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
