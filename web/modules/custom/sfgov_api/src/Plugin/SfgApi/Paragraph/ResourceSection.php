<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_resource_section",
 *   title = @Translation("Paragraph resource_section"),
 *   bundle = "resource_section",
 *   wag_bundle = "resource_section",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class ResourceSection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'content' => $this->getReferencedData($entity->get('field_content')->referencedEntities()),
    ];
  }

}
