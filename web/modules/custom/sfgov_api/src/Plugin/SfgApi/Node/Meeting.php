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

}
