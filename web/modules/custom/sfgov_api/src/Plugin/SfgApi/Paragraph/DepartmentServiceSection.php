<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_department_service_section",
 *   title = @Translation("Paragraph department_service_section"),
 *   bundle = "department_service_section",
 *   wag_bundle = "services",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class DepartmentServiceSection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $referenced_services = $this->getReferencedEntity($entity->get('field_dept_service_sect_services')->referencedEntities(), TRUE);
    $services = [];
    foreach ($referenced_services as $service_id) {
      $services[] = [
        'type' => 'page',
        'value' => $service_id,
      ];
    }
    return [
      'title' => $entity->get('field_dept_service_section_title')->value,
      'services' => $services,
    ];
  }

}
