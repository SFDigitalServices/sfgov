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
 *   shape = {},
 * )
 */
class Department extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'description' => $entity->get('field_description')->value,
      'field_alert_text' => [
        'type' => 'alert',
        'value' => [
          'description' => $entity->get('field_alert_text')->value,
          'expiration_date' => $entity->get('field_alert_expiration_date')->value,
        ],
      ],
      'spotlight_primary' => $this->getReferencedData($entity->get('field_spotlight')->referencedEntities()),
      'quicklinks' => $this->getReferencedData($entity->get('field_featured_items')->referencedEntities()),
      'meeting_information' => $this->getReferencedData($entity->get('field_public_body_meetings')->referencedEntities()),
      'meeting_archive_date' => $entity->get('field_meeting_archive_date')->value,
      'meeting_archive_url' => $entity->get('field_meeting_archive_url')->getValue() ? $entity->get('field_meeting_archive_url')->getValue()[0]['uri'] : '',
      'services' => $this->getReferencedData($entity->get('field_department_services')->referencedEntities()),
      'spotlight_secondary' => $this->getReferencedData($entity->get('field_spotlight2')->referencedEntities()),
      'resources' => $this->getResourceFieldValue($entity),
      'about_description' => $entity->get('field_about_description')->value ?: '',
      'child_agency_section_title' => $entity->get('field_agency_sections')->referencedEntities() ? strtolower($this->getReferencedData($entity->get('field_agency_sections')->referencedEntities())[0]['value']['title']) : '',
      'call_to_action' => $this->getReferencedData($entity->get('field_call_to_action')->referencedEntities(), 'call_to_action'),
      'social_media' => $this->getReferencedData($entity->get('field_social_media')->referencedEntities()),
      'contact' => $this->getContactFieldValue($entity),
      'public_records' => $this->getPublicRecordsFieldValue($entity),
      'archive_url' => $entity->get('field_archive_url')->getValue() ? $entity->get('field_archive_url')->getValue()[0]['uri'] : '',
      'archive_date' => $entity->get('field_archive_date')->value,
      'agency_redirect' => $entity->get('field_direct_external_url')->getValue() ? $entity->get('field_direct_external_url')->getValue()[0]['uri'] : '',
      'related_topics' => $this->getReferencedEntity($entity->get('field_topics')->referencedEntities()),
      'partner_agencies' => $this->getCorrespondingAgency($entity->get('field_departments')->referencedEntities()),

      // Extra reference.
      'related_information' => [],
      'part_of_locations' => [],
      'related_locations' => [],
      'related_transaction' => [],
      'campaigns' => [],
      'data_stories' => [],
      'events' => [],
      'news' => [],
      'information' => [],
      'locations' => [],
      'meetings' => [],
      'profiles' => [],
      'reports' => [],
      'resource_collections' => [],
      'step_by_steps' => [],
      'topics' => [],
      'transactions' => [],
      'agencies' => [],
      'agency' => [],
      'related_child_agencies' => [],
      'relatedcontentchildagency_set' => [],
      'public_records' => [],
    ];
  }

  /**
   * Get the contact field value.
   *
   * @param object $entity
   *   The entity object.
   *
   * @return array
   *   The contact field value.
   */
  public function getContactFieldValue($entity) {
    $contact = [];
    if ($address = $entity->get('field_address')->referencedEntities()) {
      $contact[] = [
        'type' => 'address',
        'value' => $this->getReferencedEntity($address, TRUE)[0],
      ];
    }
    // @todo , blocked until we figure out emails title required.
    // $this->getReferencedData($entity->get('field_email')->referencedEntities())[0],
    // @todo , blocked until we figure out phone numbers.
    // $this->getReferencedData($entity->get('field_phone_numbers')->referencedEntities())[0],
    return $contact;
  }

  /**
   * Get the public records field value.
   *
   * @param object $entity
   *   The entity object.
   *
   * @return string
   *   The public records field value.
   */
  public function getPublicRecordsFieldValue($entity) {
    $public_record_field_value = '';
    $public_record_value = strtolower($entity->get('field_req_public_records')->value);
    if ($public_record_value) {
      $public_record_field_name = 'field_req_public_records_' . $public_record_value;
      $public_record_field_value = $entity->get($public_record_field_name)->value;
    }

    return $public_record_field_value;
  }

  /**
   * Get the resource field value.
   *
   * @param object $entity
   *   The entity object.
   *
   * @return array
   *   The resource field value.
   */
  public function getResourceFieldValue($entity) {
    // This field is very tangled and nested in Drupal and shaped completely
    // differently in wagtail. Its a lot easier to just pull it all manually
    // rather than use the plugin system.
    $resource_field_values = [];
    $resource_sections = $entity->get('field_resources')->referencedEntities();

    if (!$resource_sections) {
      return $resource_field_values;
    }

    foreach ($resource_sections as $resource_section) {
      $resources = $resource_section->get('field_resources')->referencedEntities();
      foreach ($resources as $resource) {
        if ($resource->bundle() === 'resource_entity') {
          $resource_value = $resource->get('field_resource')->referencedEntities() ? $resource->get('field_resource')->referencedEntities()[0] : NULL;
          if ($resource_value) {
            $link_data = $this->generateLinks($resource_value->get('field_url')->getValue())[0];
            if ($link_data['value']['link_to'] === 'url') {
              $resource_field_values[] = [
                'type' => 'external_link',
                'value' => [
                  'url' => $resource_value->get('field_url')->getValue()[0]['uri'],
                  'title' => $resource_value->get('title')->value ?: '',
                  'description' => $resource_value->get('field_description')->value,
                ],
              ];
            }
            elseif ($link_data['value']['link_to'] === 'page') {
              $resource_field_values[] = [
                'type' => 'page',
                'value' => $link_data['value']['page'],
              ];
            }
          }
        }
        elseif ($resource->bundle() === 'resource_node') {
          $resource_field_values[] = [
            'type' => 'page',
            'value' => $this->getReferencedEntity($resource->get('field_node')->referencedEntities(), TRUE)[0],
          ];
        }

      }
    }

    $data = [
      'type' => 'resources',
      'value' => [
        'title' => $entity->get('field_resources')->referencedEntities() ? $entity->get('field_resources')->referencedEntities()[0]->get('field_title')->value : '',
        'resources' => $resource_field_values,
      ],
    ];

    return [$data];
  }

  /**
   * Get agency that is supposed to be referenced by the department field.
   *
   * @param object $entities
   *   The entities object.
   *
   * @return array
   *   The corresponding agency.
   */
  public function getCorrespondingAgency($entities) {
    // @todo remove this function completely once the data is properly updated and All public bodies are removed.
    $entities_data = [];
    foreach ($entities as $entity) {
      if ($entity->bundle() === 'department') {
        $entities_data[] = $this->getReferencedEntity([$entity])[0];
      }
      // Sometimes it references public bodies which aren't being migrated.
      if ($entity->bundle() === 'public_body') {
        // $entities_data[] = 'Public body referenced in department ' . $entity->id();
        // $this->addPluginError('Public body referenced in department', $entity->id());
      }
    }
    return $entities_data;
  }

}
