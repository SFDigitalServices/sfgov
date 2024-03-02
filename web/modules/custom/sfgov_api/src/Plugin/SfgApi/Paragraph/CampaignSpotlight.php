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
 * )
 */
class CampaignSpotlight extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo , blocked by image field issue.
      'image' => $this->getReferencedEntity($entity->get('field_spotlight_img')->referencedEntities(), TRUE)[0],
      'title' => $entity->get('field_title')->value,
      'button' => $this->collapseParagraph($this->getReferencedData($entity->get('field_spotlight_button')->referencedEntities())),
      'description' => $entity->get('field_description')->value,
      'orientation' => $this->editFieldValue($entity->get('field_is_reversed')->value, [0 => 'right', 1 => 'left']),
      // @todo This doesn't have a corresponding field in drupal.
      // 'banner_size' => '',
    ];
  }

  /**
   * Collapse paragraph data.
   */
  public function collapseParagraph($paragraph_data) {
    if ($paragraph_data) {
      return $paragraph_data[0]['value'][0]['value'];
    }
    return NULL;
  }

}
