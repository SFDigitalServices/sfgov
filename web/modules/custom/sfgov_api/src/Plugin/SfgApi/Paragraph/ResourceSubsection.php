<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_resource_subsection",
 *   title = @Translation("Paragraph resource_subsection"),
 *   bundle = "resource_subsection",
 *   wag_bundle = "resource_subsection",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class ResourceSubsection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'resources' => $this->getReferencedData($entity->get('field_resources')->referencedEntities()),
      'title' => $entity->get('field_title')->value,
    ];
  }

}
