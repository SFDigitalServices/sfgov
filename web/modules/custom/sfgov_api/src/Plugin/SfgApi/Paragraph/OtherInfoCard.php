<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_other_info_card",
 *   title = @Translation("Paragraph other_info_card"),
 *   bundle = "other_info_card",
 *   wag_bundle = "other_info_card",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class OtherInfoCard extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_resources' => $this->getReferencedData($entity->get('field_resources')->referencedEntities()),
      'field_title' => $entity->get('field_title')->value,
    ];
  }

}
