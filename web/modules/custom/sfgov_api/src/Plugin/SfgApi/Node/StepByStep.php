<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_step_by_step",
 *   title = @Translation("Node Step By Step"),
 *   bundle = "step_by_step",
 *   wag_bundle = "StepByStep",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class StepByStep extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_description')->value,
      'intro' => $entity->get('field_intro_text')->value,
      // 'related_content_topics' => $this->getReferencedData($entity->get('field_topics')->referencedEntities(), TRUE),
      'steps' => $this->getReferencedData($entity->get('field_process_steps')->referencedEntities(), 'step'),
    ];
  }

}
