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
      // extra array here is to force the data into a shape that streamfields.
      // expect.
      'date_time' => [$this->setToStreamField($date_data, 'date_time')],
      // // 'meeting_location' => // blocked by addresses
      'overview' => $entity->get('body')->value,
      // blocked by inconsistent document upload. AND not being able to
      // reference files in streamfields.
      // 'agenda' => $this->getReferencedData($entity->get('field_agenda')->referencedEntities()),

      // blocked by needing to remove the "internal" option from video ui
      // 'videos' => $this->getReferencedData($entity->get('field_videos')->referencedEntities()),

      'notices' => $this->getReferencedData($entity->get('field_regulations_accordions')->referencedEntities()),
      // blocked by inconsistent document upload.
      // 'meeting_documents' => $this->getReferencedData($entity->get('field_meeting_artifacts')->referencedEntities()),
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
      if ($start_time === '00:00:00' && $end_time === '23:59:00') {
        $is_all_day = TRUE;
      }
      // If the end time is '11:59' on the day of the start time,
      // hide it from display. This is how editors
      // can indicate that there is no end time.
      if ($end_time === '23:59:00') {
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
      // @todo, change this back to a boolean once its fixed on the wagtail side.
      'include_end_date_time' => $include_end_date_time ? 'yes' : 'no',
    ];
    return $data;
  }

}
