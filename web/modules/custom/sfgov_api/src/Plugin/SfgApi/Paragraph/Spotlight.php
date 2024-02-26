<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_spotlight",
 *   title = @Translation("Paragraph spotlight"),
 *   bundle = "spotlight",
 *   wag_bundle = "spotlight",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {
 *     "media_image",
 *     "paragraph_button",
 *   }
 * )
 */
class Spotlight extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_title')->value,
      'description' => $entity->get('field_description')->value,
      'button' => $this->collapseParagraph($this->getReferencedData($entity->get('field_spotlight_button')->referencedEntities())),

      // @todo , blocked by image field issue.
      // 'image' => $this->getReferencedEntity($entity->get('field_spotlight_image')->referencedEntities(), TRUE)[0]
      // 'img' => $this->getReferencedEntity($entity->get('field_spotlight_img')->referencedEntities(), TRUE)[0]
    ];
  }

  /**
   *
   */
  public function collapseParagraph($paragraph_data) {
    // @todo , this breaks if the link is internal. Add some way to collapse data on the button paragraph plugin?
    if ($paragraph_data) {
      return $paragraph_data[0]['value'][0]['value'];
    }
    return NULL;
  }

}
