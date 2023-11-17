<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_transaction",
 *   title = @Translation("Node transaction"),
 *   bundle = "transaction",
 *   wag_bundle = "Transaction",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Transaction extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is incomplete and is only built to demonstrate relationships to other entities
      'field_cost' => $entity->get('field_cost')->value,
      'field_custom_section' => $entity->get('field_custom_section')->value,
      'field_departments' => $entity->get('field_departments')->value,
      'field_description' => $entity->get('field_description')->value,
      'field_direct_external_url' => $entity->get('field_direct_external_url')->value,
      'field_do_not_show_on_topic_pages' => $entity->get('field_do_not_show_on_topic_pages')->value,
      'field_help' => $entity->get('field_help')->value,
      'field_related_content' => $entity->get('field_related_content')->value,
      'field_sort_title' => $entity->get('field_sort_title')->value,
      'field_special_cases' => $entity->get('field_special_cases')->value,
      'field_step_email' => $entity->get('field_step_email')->value,
      'field_step_in_person' => $entity->get('field_step_in_person')->value,
      'field_step_mail' => $entity->get('field_step_mail')->value,
      'field_step_online' => $entity->get('field_step_online')->value,
      'field_step_other' => $entity->get('field_step_other')->value,
      'field_step_other_title' => $entity->get('field_step_other_title')->value,
      'field_step_phone' => $entity->get('field_step_phone')->value,
      'field_things_to_know' => $entity->get('field_things_to_know')->value,
      'field_topics' => $entity->get('field_topics')->value,
      'field_transaction_purpose' => $entity->get('field_transaction_purpose')->value,
      'field_transactions' => $entity->get('field_transactions')->value,
    ];
  }

}
