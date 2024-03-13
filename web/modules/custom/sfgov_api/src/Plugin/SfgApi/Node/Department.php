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
 *   is_stub = {},
 * )
 */
class Department extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is only fetching data. needs to be massaged.
      'field_about_description' => $entity->get('field_about_description')->value,
      'field_about_or_description' => $entity->get('field_about_or_description')->value,
      'field_address' => $this->getReferencedEntity($entity->get('field_address')->referencedEntities()),
      'field_agency_sections' => $this->getReferencedData($entity->get('field_agency_sections')->referencedEntities()),
      'field_alert_expiration_date' => $entity->get('field_alert_expiration_date')->value,
      'field_alert_text' => $entity->get('field_alert_text')->value,
      'field_archive_date' => $entity->get('field_archive_date')->value,
      'field_archive_url' => $this->generateLinks($entity->get('field_archive_url')->getValue()),
      'field_call_to_action' => $this->getReferencedData($entity->get('field_call_to_action')->referencedEntities()),
      'field_department_code' => $entity->get('field_department_code')->value,
      'field_department_services' => $entity->get('field_department_services')->value,
    // @todo . field_departments Includes public_body nodes.
      'field_departments' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      'field_description' => $entity->get('field_description')->value,
      'field_direct_external_url' => $this->generateLinks($entity->get('field_direct_external_url')->getValue()),
      'field_email' => $this->getReferencedData($entity->get('field_email')->referencedEntities()),
      'field_featured_items' => $this->getReferencedData($entity->get('field_featured_items')->referencedEntities()),
      'field_image' => $this->getReferencedEntity($entity->get('field_image')->referencedEntities()),
      'field_include_in_list' => $this->editFieldValue($entity->get('field_include_in_list')->value, [
        1 => TRUE,
        0 => FALSE,
      ]),
      'field_meeting_archive_date' => $entity->get('field_meeting_archive_date')->value,
      'field_meeting_archive_url' => $this->generateLinks($entity->get('field_direct_external_url')->getValue()),
      'field_parent_department' => $this->getReferencedEntity($entity->get('field_parent_department')->referencedEntities()),
      'field_people' => $this->getReferencedData($entity->get('field_people')->referencedEntities()),
      'field_phone_numbers' => $this->getReferencedData($entity->get('field_phone_numbers')->referencedEntities()),
      'field_public_body_meetings' => $this->getReferencedData($entity->get('field_public_body_meetings')->referencedEntities()),
      'field_req_public_records' => $entity->get('field_req_public_records')->value,
      'field_req_public_records_email' => $entity->get('field_req_public_records_email')->value,
      'field_req_public_records_link' => $this->generateLinks($entity->get('field_req_public_records_link')->getValue()),
      'field_req_public_records_phone' => $entity->get('field_req_public_records_phone')->value,
    // @todo . field_resources is requesting eck resource entities which are not being migrated.
      'field_resources' => $this->getReferencedData($entity->get('field_resources')->referencedEntities()),
      'field_social_media' => $this->getReferencedData($entity->get('field_social_media')->referencedEntities()),
      'field_spotlight' => $this->getReferencedData($entity->get('field_spotlight')->referencedEntities()),
      'field_spotlight2' => $this->getReferencedData($entity->get('field_spotlight2')->referencedEntities()),
      'field_topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),
    ];
  }

}
