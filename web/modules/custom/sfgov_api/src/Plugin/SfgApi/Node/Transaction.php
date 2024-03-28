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
    $derp = true;
    return [
      'description' => $entity->get('field_description')->value ?? '',
      'cost' => $this->getReferencedData($entity->get('field_cost')->referencedEntities()),
      'things_to_know' => $this->getReferencedData($entity->get('field_things_to_know')->referencedEntities()),
      'what_to_do' => $this->getWhatToDoValues($entity),
      'supporting_information' => $this->getReferencedData($entity->get('field_special_cases')->referencedEntities(), 'title_and_text'),
      'custom_section' => $this->getReferencedData($entity->get('field_custom_section')->referencedEntities(), 'title_and_text'),
      'good_for_community' => $this->getReferencedData($entity->get('field_transaction_purpose')->referencedEntities()),
      'get_help' => $this->getGetHelpValues($entity),
      'hide_on_topic_pages' => $this->editFieldValue($entity->get('field_do_not_show_on_topic_pages')->value, [
        1 => TRUE,
        0 => FALSE,
        NULL => FALSE,
      ]),
      'partner_agencies' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),

      // Not sure what this is but Wagtail needs it.
      'information' => [],
    ];
  }

  public function getWhatToDoValues($entity) {
    $step_fields = [
      'field_step_email',
      'field_step_in_person',
      'field_step_mail',
      'field_step_online',
      'field_step_other',
      'field_step_phone',
    ];

    $data = [];
    foreach ($step_fields as $field_name => $value) {
      if ($entity->get($value)->referencedEntities()) {
        foreach ($this->getReferencedData($entity->get($value)->referencedEntities()) as $key => $value) {
          if ($value) {
            switch ($value['type']) {
              case 'step':
                $data[] = $this->getSteps($value['value']);
                break;
              case 'callout':
                $data[] = [
                  'type' => 'callout',
                  'value' => $value['value'],
                ];
                break;
            }
          }
        }
      }
    }

    return $data;
  }

  public function getGetHelpValues($entity) {
    $values = $this->getReferencedData($entity->get('field_help')->referencedEntities());
    $data = [];
    foreach ($values as $key => $value) {
      switch ($value['type']) {
        case 'email_addresses':
          foreach ($value['value']['emails'] as $value) {
            $data[] = $value;
          }
          break;
        case 'phone_numbers':
          foreach ($value['value']['phone_numbers'] as $value) {
            $data[] = $value;
          }
          break;

        default:
          # code...
          break;
      }
    }
    $derp = true;

    return $data;
  }

  public function getSteps($data) {
    $steps = [
      'section_title' => $data['title'] ?? 'temp',
      'section_specifics' => [],
    ];
    foreach ($data['value'] as $key => $value) {
      // Button paragraphs are flattened by default because most cases do not
      // need the type value. Its different here, we need to manually add it
      // back in.
      if (!isset($value['type'])) {
        if (isset($value['link_to'])) {
          $steps['section_specifics'][] = [
            'type' => 'button_link',
            'value' => $value,
          ];
        }
      }
      else {
        $steps['section_specifics'][] = $value;
      }
    }

    $data = [
      'type' => 'what_to_do_step',
      'value' => $steps,
    ];
    return $data;
  }
}
