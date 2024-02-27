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
 *   wag_bundle = "thing_to_know",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {},
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
