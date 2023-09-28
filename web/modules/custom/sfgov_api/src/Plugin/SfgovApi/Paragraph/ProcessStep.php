<?php

namespace Drupal\sfgov_api\Plugin\SfgovApi\Paragraph;

use Drupal\sfgov_api\SfgovApiParagraphPluginBase;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgovApi(
 *   id = "paragraph_process_step",
 *   title = @Translation("Paragraph Process Step"),
 *   bundle = "process_step",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class ProcessStep extends SfgovApiParagraphPluginBase {

  public function setCustomData($entity) {
  return [
    'field_cost' => $this->getReferencedData($entity->get('field_cost')->referencedEntities()),
    'field_process_optional' => $entity->get('field_process_optional')->value,
    'field_process_step_description' => $entity->get('field_process_step_description')->value,
    'field_process_step_type' => $entity->get('field_process_step_type')->value,
    'field_text_time' => $entity->get('field_text_time')->value,
    'field_title' => $entity->get('field_title')->value,
    'field_transaction' => $this->getReferencedData($entity->get('field_transaction')->referencedEntities(), TRUE),
  ];
  }

}
