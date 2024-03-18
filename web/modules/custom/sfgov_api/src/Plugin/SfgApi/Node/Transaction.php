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
 *   is_stub = {},
 * )
 */
class Transaction extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {

    return [
      // Not sure what this is but Wagtail needs it.
      'information' => [],
      // @todo this plugin is only fetching data. needs to be massaged.
      'description' => $entity->get('field_description')->value,
      'cost' => $this->getReferencedData($entity->get('field_cost')->referencedEntities()),
      'things_to_know' => $this->getReferencedData($entity->get('field_things_to_know')->referencedEntities()),
      // what_to_do => '', // weird combo field
      // supporting_information => '', // unknown field
      'custom_section' => $this->getReferencedData($entity->get('field_custom_section')->referencedEntities()),
      'good_for_community' => $this->getReferencedData($entity->get('field_transaction_purpose')->referencedEntities()),
      // 'get_help' => $this->getReferencedData($entity->get('field_help')->referencedEntities()), // unclear
      'hide_on_topic_pages' => $this->editFieldValue($entity->get('field_do_not_show_on_topic_pages')->value, [
        1 => TRUE,
        0 => FALSE,
      ]),
      'partner_agencies' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),


      // 'field_direct_external_url' => $this->generateLinks($entity->get('field_direct_external_url')->getvalue()),
      // 'field_related_content' => $this->getReferencedEntity($entity->get('field_related_content')->referencedEntities(), FALSE, TRUE),
      // 'field_sort_title' => $entity->get('field_sort_title')->value,
      // 'field_special_cases' => $this->getReferencedData($entity->get('field_special_cases')->referencedEntities()),
      // 'field_step_email' => $this->getReferencedData($entity->get('field_step_email')->referencedEntities()),
      // 'field_step_in_person' => $this->getReferencedData($entity->get('field_step_in_person')->referencedEntities()),
      // 'field_step_mail' => $this->getReferencedData($entity->get('field_step_mail')->referencedEntities()),
      // 'field_step_online' => $this->getReferencedData($entity->get('field_step_online')->referencedEntities()),
      // 'field_step_other' => $this->getReferencedData($entity->get('field_step_other')->referencedEntities()),
      // 'field_step_other_title' => $entity->get('field_step_other_title')->value,
      // 'field_step_phone' => $this->getReferencedData($entity->get('field_step_phone')->referencedEntities()),
      // 'field_transactions' => $this->getReferencedEntity($entity->get('field_transactions')->referencedEntities()),
    ];
  }

}
