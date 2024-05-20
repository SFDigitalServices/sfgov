<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_campaign",
 *   title = @Translation("Paragraph campaign"),
 *   bundle = "campaign",
 *   wag_bundle = "campaign",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class Campaign extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_title')->value,
      'field_link' => $this->generateLinks($entity->get('field_link')->getvalue()),
      'field_media' => $this->getReferencedEntity($entity->get('field_media')->referencedEntities()),
    ];
  }

}
