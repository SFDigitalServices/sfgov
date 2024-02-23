<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_department",
 *   title = @Translation("Node department"),
 *   bundle = "department",
 *   wag_bundle = "Agency",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {
 *    "media_image",
 *   }
 * )
 */
class Department extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is incomplete and only exists for entity referencing
      // at the moment.
      // 'field_about_description' => $entity->get('field_about_description')->value,
      // 'field_about_or_description' => $entity->get('field_about_or_description')->value,
      // 'field_address' => $entity->get('field_address')->value,
      // 'field_agency_sections' => $entity->get('field_agency_sections')->value,
      // 'field_alert_expiration_date' => $entity->get('field_alert_expiration_date')->value,
      // 'field_alert_text' => $entity->get('field_alert_text')->value,
      // 'field_archive_date' => $entity->get('field_archive_date')->value,
      // 'field_archive_url' => $entity->get('field_archive_url')->value,
      // 'field_call_to_action' => $entity->get('field_call_to_action')->value,
      // 'field_department_code' => $entity->get('field_department_code')->value,
      // 'field_department_services' => $entity->get('field_department_services')->value,
      // 'field_departments' => $entity->get('field_departments')->value,
      // 'field_description' => $entity->get('field_description')->value,
      // 'field_direct_external_url' => $entity->get('field_direct_external_url')->value,
      // 'field_email' => $entity->get('field_email')->value,
      // 'field_featured_items' => $entity->get('field_featured_items')->value,
      // 'field_image' => $entity->get('field_image')->value,
      // 'field_include_in_list' => $entity->get('field_include_in_list')->value,
      // 'field_meeting_archive_date' => $entity->get('field_meeting_archive_date')->value,
      // 'field_meeting_archive_url' => $entity->get('field_meeting_archive_url')->value,
      // 'field_parent_department' => $entity->get('field_parent_department')->value,
      // 'field_people' => $entity->get('field_people')->value,
      // 'field_phone_numbers' => $entity->get('field_phone_numbers')->value,
      // 'field_public_body_meetings' => $entity->get('field_public_body_meetings')->value,
      // 'field_req_public_records' => $entity->get('field_req_public_records')->value,
      // 'field_req_public_records_email' => $entity->get('field_req_public_records_email')->value,
      // 'field_req_public_records_link' => $entity->get('field_req_public_records_link')->value,
      // 'field_req_public_records_phone' => $entity->get('field_req_public_records_phone')->value,
      // 'field_resources' => $entity->get('field_resources')->value,
      // 'field_social_media' => $entity->get('field_social_media')->value,
      // 'field_spotlight' => $entity->get('field_spotlight')->value,
      // 'field_spotlight2' => $entity->get('field_spotlight2')->value,
      // 'field_topics' => $entity->get('field_topics')->value,
    ];
  }

}
