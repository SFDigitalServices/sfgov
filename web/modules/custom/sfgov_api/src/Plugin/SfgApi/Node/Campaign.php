<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_campaign",
 *   title = @Translation("Node campaign"),
 *   bundle = "campaign",
 *   wag_bundle = "Campaign",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Campaign extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
    // @todo this plugin is incomplete and only exists for entity referencing
    // at the moment.
    // Notes:
    // - Fields below are just a starting point, refine these down to the fields you actually want.
    // - The ->value function won't work for all fields and is just there to kickstart the process.
    // - Make sure to manually double check and update the wag_bundle in the annotation above.
    // - To small adjustments to the data only relevant to this entity, you add functions to this plugin.
    // - Look at ApiFieldHelperTrait.php for broad functions that can be used across all entities (like entity references).
      'field_campaign_about' => $entity->get('field_campaign_about')->value,
      'field_campaign_theme' => $entity->get('field_campaign_theme')->value,
      'field_contents' => $entity->get('field_contents')->value,
      'field_departments' => $entity->get('field_departments')->value,
      'field_dept' => $entity->get('field_dept')->value,
      'field_header_spotlight' => $entity->get('field_header_spotlight')->value,
      'field_links' => $entity->get('field_links')->value,
      'field_logo' => $entity->get('field_logo')->value,
      'field_social_media_embed' => $entity->get('field_social_media_embed')->value,
      'field_spotlight' => $entity->get('field_spotlight')->value,
      'field_top_facts' => $entity->get('field_top_facts')->value,
    ];
  }

}
