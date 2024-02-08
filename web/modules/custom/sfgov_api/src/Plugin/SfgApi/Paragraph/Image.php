<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_image",
 *   title = @Translation("Paragraph image"),
 *   bundle = "image",
 *   wag_bundle = "image",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Image extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $derp = TRUE;
    return [
      'value' => $this->getReferencedEntity($entity->get('field_image')->referencedEntities(), TRUE, TRUE),
    ];
  }

}
