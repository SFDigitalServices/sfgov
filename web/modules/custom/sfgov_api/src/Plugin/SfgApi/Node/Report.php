<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Node;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "node_report",
 *   title = @Translation("Node report"),
 *   bundle = "report",
 *   wag_bundle = "Report",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class Report extends SfgApiNodeBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is only fetching data. needs to be massaged.
      // 'body' => $entity->get('body')->value,
      'date' => $entity->get('field_date_only')->value,
      // 'field_departments' => $this->getReferencedEntity($entity->get('field_departments')->referencedEntities()),
      // 'field_description' => $entity->get('field_description')->value,
      // 'field_print_version' => $this->getReferencedEntity($entity->get('field_print_version')->referencedEntities()),
      // 'field_spotlight' => $this->getReferencedData($entity->get('field_spotlight')->referencedEntities()),
    ];
  }

}
