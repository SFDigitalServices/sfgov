<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_callout",
 *   title = @Translation("Paragraph callout"),
 *   bundle = "callout",
 *   wag_bundle = "callout",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Callout extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_text' => $entity->get('field_text')->value,
    ];
  }

}
