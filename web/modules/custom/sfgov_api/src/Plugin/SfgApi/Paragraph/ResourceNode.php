<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_resource_node",
 *   title = @Translation("Paragraph resource_node"),
 *   bundle = "resource_node",
 *   wag_bundle = "resource_node",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class ResourceNode extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // @todo , this paragraph references all node bundles, some of which aren't
    // being migrated. Need a way to handle this.
    return [
      'field_node' => $this->getReferencedEntity($entity->get('field_node')->referencedEntities()),
    ];
  }

}
