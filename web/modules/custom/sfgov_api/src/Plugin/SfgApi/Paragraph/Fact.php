<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_fact",
 *   title = @Translation("Paragraph fact"),
 *   bundle = "fact",
 *   wag_bundle = "title_and_text",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {
 *     "media_image",
 *   }
 * )
 */
class Fact extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_title')->value,
      'description' => $entity->get('field_description')->value,
      'image' => $this->getReferencedEntity($entity->get('field_image')->referencedEntities()),
    ];
  }

}
