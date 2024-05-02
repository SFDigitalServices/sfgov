<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_image_with_text",
 *   title = @Translation("Paragraph image_with_text"),
 *   bundle = "image_with_text",
 *   wag_bundle = "image_with_text",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class ImageWithText extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_description')->value,
      'image' => $this->getReferencedEntity($entity->get('field_image')->referencedEntities()),
      'title' => $entity->get('field_title')->value,
    ];
  }

}
