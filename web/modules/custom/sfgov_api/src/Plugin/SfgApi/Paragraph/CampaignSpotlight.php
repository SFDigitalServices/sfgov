<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_campaign_spotlight",
 *   title = @Translation("Paragraph campaign_spotlight"),
 *   bundle = "campaign_spotlight",
 *   wag_bundle = "spotlight",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class CampaignSpotlight extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $button_data = $this->getReferencedData($entity->get('field_spotlight_button')->referencedEntities());
    $empty_button = [
      'url' => '',
      'page' => NULL,
      'link_to' => '',
      'link_text' => '',
    ];
    $button_value = $button_data ? $button_data[0] : $empty_button;

    return [
      // @todo , blocked by image field issue.
      'image' => $this->getReferencedEntity($entity->get('field_spotlight_img')->referencedEntities(), TRUE)[0],
      'title' => $entity->get('field_title')->value,
      'button' => $button_value,
      'banner_size' => 'half',
      'description' => $entity->get('field_description')->value,
      'orientation' => 'left',
    ];
  }

}
