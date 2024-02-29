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
 *   referenced_plugins = {
 *     "paragraph_resource_section",
 *     "location_physical",
 *     "paragraph_phone",
 *     "paragraph_agenda_item",
 *     "paragraph_video_external",
 *     "paragraph_video",
 *     "paragraph_accordion_item",
 *     "node_department",
 *   }
 * )
 */
class Meeting extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $date_data = $this->convertSmartDate($entity->get('field_smart_date')->getValue()[0]);
    $meeting_address = [
      'type' => 'address',
      'value' => $this->getReferencedEntity($entity->get('field_address')->referencedEntities(), TRUE, TRUE),
    ];
    $meeting_online = [];

    if ($entity->get('field_location_online')->value == 1) {
      // @todo use existing link function.
      $meeting_online = [
        'type' => 'online',
        'value' => [
          'link' => [
            // Hardcoding as an external URL since the field in Drupal only
            // allows for external links.
            'url' => $entity->get('field_link')->uri,
            'page' => NULL,
            'link_to' => 'url',
            'link_text' => $entity->get('field_link')->title,
          ],
          // @todo Works, but blocked by needing to make decisions about phone
          // numbers that weren't validated properly.
          'phone' => $this->getReferencedData($entity->get('field_phone_numbers')->referencedEntities()),
          'phone' => [],
          'description' => $entity->get('field_abstract')->value ?: '',
        ],
      ];
    }
    $meeting_location = [$meeting_address, $meeting_online];
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
      'primary_agency' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities(), FALSE, TRUE),
    ];
  }

}
