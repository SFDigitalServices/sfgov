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
 *   referenced_plugins = {
 *     "node_about",
 *     "node_department",
 *     "node_campaign",
 *     "node_data_story",
 *     "node_event",
 *     "node_form_confirmation_page",
 *     "node_form_page",
 *     "node_information_page",
 *     "node_location",
 *     "node_meeting",
 *     "node_news",
 *     "node_person",
 *     "node_resource_collection",
 *     "node_step_by_step",
 *     "node_topic",
 *     "node_transaction",
 *   }
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
