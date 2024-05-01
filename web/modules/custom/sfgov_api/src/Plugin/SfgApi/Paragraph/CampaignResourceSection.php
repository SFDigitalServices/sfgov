<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_campaign_resource_section",
 *   title = @Translation("Paragraph campaign_resource_section"),
 *   bundle = "campaign_resource_section",
 *   wag_bundle = "campaign_resource_section",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class CampaignResourceSection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'content' => $entity->get('field_content')->value,
      'title' => $entity->get('field_title')->value,
    ];
  }

}
