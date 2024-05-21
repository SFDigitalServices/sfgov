<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_thing_to_know",
 *   title = @Translation("Paragraph thing_to_know"),
 *   bundle = "thing_to_know",
 *   wag_bundle = "title_and_text",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class ThingToKnow extends SfgApiParagraphBase {

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
