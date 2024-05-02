<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_campaign_resources",
 *   title = @Translation("Paragraph campaign_resources"),
 *   bundle = "campaign_resources",
 *   wag_bundle = "campaign_resources",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class CampaignResources extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'file' => $this->getReferencedEntity($entity->get('field_file')->referencedEntities()),
      'resources' => $entity->get('field_resources')->value,
      'title' => $entity->get('field_title')->value,
    ];
  }

}
