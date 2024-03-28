<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_meeting",
 *   title = @Translation("Node meeting"),
 *   bundle = "meeting",
 *   wag_bundle = "Meeting",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class Meeting extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $date_data = $this->convertSmartDate($entity->get('field_smart_date')->getValue()[0]);
    $meeting_location = [];
    if ($entity->get('field_address')->referencedEntities()) {
      $meeting_location[] = [
        'type' => 'address',
        'value' => $this->getReferencedEntity($entity->get('field_address')->referencedEntities(), TRUE, TRUE),
      ];
    }

    if ($entity->get('field_location_online')->value == 1) {
      $meeting_location[] = [
        'type' => 'online',
        'value' => [
          'link' => $this->generateLinks($entity->get('field_link')->getvalue())[0]['value'],
          'phone' => $this->getReferencedData($entity->get('field_phone_numbers')->referencedEntities()),
          'description' => $entity->get('field_abstract')->value ?: '',
        ],
      ];
    }
    return [
      'cancelled' => $entity->get('field_meeting_cancel')->value,
      // Extra array here is to force the data into a shape that streamfields
      // expect.
      'date_time' => [$this->setToStreamField($date_data, 'date_time')],
      'meeting_location' => $meeting_location,
      'overview' => $entity->get('body')->value,
      'agenda' => $this->getReferencedData($entity->get('field_agenda')->referencedEntities()),
      'videos' => $this->getReferencedData($entity->get('field_videos')->referencedEntities()),
      'notices' => $this->getReferencedData($entity->get('field_regulations_accordions')->referencedEntities()),
      'meeting_documents' => $this->getReferencedData($entity->get('field_meeting_artifacts')->referencedEntities()),
      'primary_agency' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities(), FALSE, TRUE) ?: '',
    ];
  }

}
