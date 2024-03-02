<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_event",
 *   title = @Translation("Node event"),
 *   bundle = "event",
 *   wag_bundle = "Event",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Event extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $date_data = $this->convertSmartDate($entity->get('field_smart_date')->getValue()[0]);
    $contact = [];
    if ($email = $entity->get('field_email')->referencedEntities()) {
      $contact[] = $this->getReferencedData($email)[0];
    }
    // @todo Works, but blocked by needing to make decisions about phone numbers
    // that weren't validated properly.
    if ($phone = $entity->get('field_phone_numbers')->referencedEntities()) {
      $contact[] = $this->getReferencedData($phone)[0];
    }
    $location = [];
    if ($entity->get('field_location_online')->value == 1) {
      $location[] = [
        'type' => 'online',
        'value' => NULL,
      ];
    }
    // @todo Blocked by address issue. Use same logic as "meeting" plugin.
    if ($address_entity = $entity->get('field_address')->referencedEntities()) {
      $location[] = $this->getReferencedEntity($address_entity, TRUE);
    }
    return [
      'description' => $entity->get('field_description')->value,
      'date_time' => [$this->setToStreamField($date_data, 'date_time')],
      'cost' => $this->getReferencedData($entity->get('field_cost')->referencedEntities()),
      // @todo Blocked by optionality field issue.
      'call_to_action' => $this->getReferencedData($entity->get('field_call_to_action')->referencedEntities()),
      'image' => $this->getReferencedEntity($entity->get('field_image')->referencedEntities(), FALSE, TRUE),
      'body' => $entity->get('body')->value,
      'partner_agencies' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),
      'contact' => $contact,
      'location' => $location,
    ];
  }

}
