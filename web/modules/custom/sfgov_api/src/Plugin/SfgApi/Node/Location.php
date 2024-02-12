<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_location",
 *   title = @Translation("Node location"),
 *   bundle = "location",
 *   wag_bundle = "LocationPage",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Location extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $accordion_items = $this->getReferencedData($entity->get('field_getting_here_items')->referencedEntities());
    $sorted_accordion_items = $this->sortAccordionItems($accordion_items);
    $derp = true;
    return [
      // @todo finish stubbing out the fields.
      'description' => $entity->get('field_about_description')->value,
      // 'alert' => '',
      'location_address' => $this->getReferencedData($entity->get('field_address')->referencedEntities()),
      // 'contact' => '',
      // 'body' => '',
      // 'intro' => '',
      // 'accordions' => '',
      'parking' => $sorted_accordion_items['parking'],
      'accessibility' => $sorted_accordion_items['accessibility'],
      'public_transportation' => $sorted_accordion_items['public_transportation'],
      // 'services' => '',
      // 'about_location' => '',


      // 'body' => $entity->get('body')->value,
      // 'alert' => $entity->get('field_alert_text')->value,
      // 'field_alert_expiration_date' => $entity->get('field_alert_expiration_date')->value,
      // 'field_alert_text' => $entity->get('field_alert_text')->value,
      // 'field_at_this_location' => $entity->get('field_at_this_location')->value,
      // 'field_departments' => $entity->get('field_departments')->value,
      // 'field_getting_here_items' => $entity->get('field_getting_here_items')->value,
      // 'field_image' => $entity->get('field_image')->value,
      // 'field_intro_text' => $entity->get('field_intro_text')->value,
      // 'field_locations' => $entity->get('field_locations')->value,
      // 'field_people' => $entity->get('field_people')->value,
      // 'field_phone_numbers' => $entity->get('field_phone_numbers')->value,
      // 'field_services' => $entity->get('field_services')->value,
      // 'field_title' => $entity->get('field_title')->value,
    ];
  }

  private function sortAccordionItems($accordion_items) {
    $sorted_accordion = [];
    foreach ($accordion_items as $item) {
      $title = strtolower(str_replace(' ', '_', $item["value"]["title"]));
      switch ($title) {
        case 'parking':
          $sorted_accordion['parking'] = $item;
          break;
        case 'accessibility':
          $sorted_accordion['accessibility'] = $item;
          break;
        case 'public_transportation':
          $sorted_accordion['public_transportation'] = $item;
          break;
        default:
          $sorted_accordion[] = $item;
          break;
      }
    }
    return $sorted_accordion;
  }

}
