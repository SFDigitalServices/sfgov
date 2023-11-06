<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_process_step",
 *   title = @Translation("Paragraph Process Step"),
 *   bundle = "process_step",
 *   wag_bundle = "cost",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class ProcessStep extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'step_type' => $entity->get('field_process_step_type')->value,
      'title' => $entity->get('field_title')->value,
      'optional' => $entity->get('field_process_optional')->value,
      'time' => $entity->get('field_text_time')->value,
      'step_description' => $entity->get('field_process_step_description')->value,
      'cost' => $this->getReferencedData($entity->get('field_cost')->referencedEntities(), 'cost'),
      // 'related_content_transactions' => $this->getReferencedData($entity->get('field_transaction')->referencedEntities(), TRUE),
    ];
  }

}
