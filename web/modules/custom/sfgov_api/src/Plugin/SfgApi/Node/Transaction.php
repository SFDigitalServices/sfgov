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
 *   referenced_plugins = {
 *     "paragraph_cost",
 *     "paragraph_custom_section",
 *     "paragraph_help",
 *     "location_physical",
 *     "paragraph_phone_numbers",
 *     "paragraph_email",
 *     "node_campaign",
 *     "node_data_story",
 *     "node_information_page",
 *     "node_resource_collection",
 *     "node_step_by_step",
 *     "node_topic",
 *     "node_transaction",
 *     "paragraph_special_case",
 *     "paragraph_step",
 *     "paragraph_callout",
 *     "paragraph_thing_to_know",
 *     "paragraph_additional_info",
 *   },
 * )
 */
class Transaction extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is only fetching data. needs to be massaged.
      'field_cost' => $this->getReferencedData($entity->get('field_cost')->referencedEntities()),
      'field_custom_section' => $this->getReferencedData($entity->get('field_custom_section')->referencedEntities()),
      'field_departments' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'field_description' => $entity->get('field_description')->value,
      'field_direct_external_url' => $this->generateLinks($entity->get('field_direct_external_url')->getvalue()),
      'field_do_not_show_on_topic_pages' => $this->editFieldValue($entity->get('field_do_not_show_on_topic_pages')->value, [1 => TRUE, 0 => FALSE]),
      'field_help' => $this->getReferencedData($entity->get('field_help')->referencedEntities()),
      'field_related_content' => $this->getReferencedEntity($entity->get('field_related_content')->referencedEntities(), FALSE, TRUE),
      'field_sort_title' => $entity->get('field_sort_title')->value,
      'field_special_cases' => $this->getReferencedData($entity->get('field_special_cases')->referencedEntities()),
      'field_step_email' => $this->getReferencedData($entity->get('field_step_email')->referencedEntities()),
      'field_step_in_person' => $this->getReferencedData($entity->get('field_step_in_person')->referencedEntities()),
      'field_step_mail' => $this->getReferencedData($entity->get('field_step_mail')->referencedEntities()),
      'field_step_online' => $this->getReferencedData($entity->get('field_step_online')->referencedEntities()),
      'field_step_other' => $this->getReferencedData($entity->get('field_step_other')->referencedEntities()),
      'field_step_other_title' => $this->getReferencedData($entity->get('field_step_other_title')->referencedEntities()),
      'field_step_phone' => $this->getReferencedData($entity->get('field_step_phone')->referencedEntities()),
      'field_things_to_know' => $entity->get('field_things_to_know')->value,
      'field_topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),
      'field_transaction_purpose' => $this->getReferencedData($entity->get('field_transaction_purpose')->referencedEntities()),
      'field_transactions' => $this->getReferencedEntity($entity->get('field_transactions')->referencedEntities()),
    ];
  }

}
