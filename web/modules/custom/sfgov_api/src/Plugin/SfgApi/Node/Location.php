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
 *   is_stub = {},
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
    if ($phone = $entity->get('field_phone_numbers')->referencedEntities()) {
      $contact = $this->getReferencedData($phone);
    }

    if ($entity->get('field_alert_text')->value || $entity->get('field_alert_expiration_date')->value) {
      $alert = [
        'type' => 'alert',
        'value' => [
          'text' => $entity->get('field_alert_text')->value ?: '',
          'expiration_date' => $entity->get('field_alert_expiration_date')->value ?: '',
      ],
    ];
    }

    return [
      'description' => $entity->get('field_about_description')->value ?? '',
      'alert' => isset($alert) ? [$alert] : [],
      // For some reason addresses get cited like a streamfield so we need
      // to wrap it in an extra array.
      'location_address' => [[
        'type' => 'address',
        'value' => $this->getReferencedEntity($entity->get('field_address')->referencedEntities(), TRUE, TRUE),
      ],
      ],
      'contact' => isset($contact) ? $contact : [],
      'body' => $entity->get('body')->value ?? '',
      'intro' => $entity->get('field_intro_text')->value ?? '',
      'accordions' => $sorted_accordion_items['accordion'] ?? [],
      'parking' => $sorted_accordion_items['parking'] ?? [],
      'accessibility' => $sorted_accordion_items['accessibility'] ?? [],
      'public_transportation' => $sorted_accordion_items['public_transportation'] ?? [],
      'services' => $this->getReferencedData($entity->get('field_services')->referencedEntities()),
      'about_location' => $entity->get('field_about_description')->value ?: '',
    ];
  }

  /**
   * Sort accordion items based on their title.
   */
  private function sortAccordionItems($accordion_items) {
    $sorted_accordion = [];
    foreach ($accordion_items as $item) {
      $title = strtolower(str_replace(' ', '_', $item['value']['title']));
      switch ($title) {
        case 'parking':
          $sorted_accordion['parking'] = [$item];
          break;

        case 'accessibility':
          $sorted_accordion['accessibility'] = [$item];
          break;

        case 'public_transportation':
          $sorted_accordion['public_transportation'] = [$item];
          break;

        default:
          $sorted_accordion['accordion'][] = $item;
          break;
      }
    }

    return $sorted_accordion;
  }

}
