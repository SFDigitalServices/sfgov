<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_step",
 *   title = @Translation("Paragraph step"),
 *   bundle = "step",
 *   wag_bundle = "step",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class Step extends SfgApiParagraphBase {

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
