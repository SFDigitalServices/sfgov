<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_resource_entity",
 *   title = @Translation("Paragraph resource_entity"),
 *   bundle = "resource_entity",
 *   wag_bundle = "resource_entity",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class ResourceEntity extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_resource' => $this->getReferencedData($entity->get('field_resource')->referencedEntities()),
    ];
  }

}
