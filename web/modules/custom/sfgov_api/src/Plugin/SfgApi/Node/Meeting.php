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
 *   wag_bundle = "meeting",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Meeting extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $date_data = $this->convertSmartDate($entity->get('field_smart_date')->getValue()[0]);
    return [
      // @todo still in progress
      'cancelled' => $entity->get('field_meeting_cancel')->value,
      'date_time' => $this->setToStreamField($date_data, 'date_time'),
      // 'meeting_location' => // blocked by addresses
      'overview' => $entity->get('body')->value,
      'agenda' => $this->getReferencedData($entity->get('field_agenda')->referencedEntities()),
      // 'videos' => $this->getReferencedData($entity->get('field_videos')->referencedEntities()),
      'notices' => $this->getReferencedData($entity->get('field_regulations_accordions')->referencedEntities()),
      'meeting_documents' => $this->getReferencedData($entity->get('field_meeting_artifacts')->referencedEntities()),
      'partner_agencies' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      // 'primary_agency' => $this->getReferencedEntity($entity->get('field_public_body')->referencedEntities()),
      // 'link_text_description' => $entity->get('field_link')->value,
      // 'video_recording' => $entity->get('field_videos')->value,
      // 'field_abstract' => $entity->get('field_abstract')->value,
      // 'field_address' => $entity->get('field_address')->value,
      // 'field_agenda' => $entity->get('field_agenda')->value,
      // 'field_departments' => $entity->get('field_departments')->value,
      // 'field_dept' => $entity->get('field_dept')->value,
      // 'field_link' => $entity->get('field_link')->value,
      // 'field_location_in_person' => $entity->get('field_location_in_person')->value,
      // 'field_location_online' => $entity->get('field_location_online')->value,
      // 'field_meeting_artifacts' => $entity->get('field_meeting_artifacts')->value,
      // 'cancel' => $entity->get('field_meeting_cancel')->value,
      // 'field_phone_numbers' => $entity->get('field_phone_numbers')->value,
    ];
  }

  /**
   * Convert the smart date field to a format that can be used by the
   * date_time field. Same logic thats used in SfgovDateFormatterBase
   *
   * @param array $data
   *   The smart date field data.
   *
   * @return array
   *   The converted data.
   */
  public function convertSmartDate($data) {
    $start_date = $this->convertTimestampToFormat($data['value'], 'Y-m-d');
    $start_time = $this->convertTimestampToFormat($data['value'], 'H:i:s');
    $is_all_day = FALSE;
    $include_end_date_time = TRUE;
    $end_date = $this->convertTimestampToFormat($data['end_value'], 'Y-m-d');
    $end_time = $this->convertTimestampToFormat($data['end_value'], 'H:i:s');

    if ($start_time != $end_time) {
      // If you mark it as "all day" the smart_date saves the time values as
      // 11:59pm - 12:00am.
      if ($start_time === '00:00' && $end_time === '23:59') {
        $is_all_day = TRUE;
      }
      // If the end time is '11:59' on the day of the start time,
      // hide it from display. This is how editors
      // can indicate that there is no end time.
      if ($end_time === '23:59') {
        if ($start_date == $end_date) {
          $include_end_date_time = FALSE;
        }
      }
    }

    $data = [
      'end_date' => $end_date,
      'end_time' => $end_time,
      'is_all_day' => $is_all_day,
      'start_date' => $start_date,
      'start_time' => $start_time,
      'include_end_date_time' => $include_end_date_time,
    ];
    return $data;
  }

}
