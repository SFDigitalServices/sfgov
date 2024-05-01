<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_department_content",
 *   title = @Translation("Paragraph department_content"),
 *   bundle = "department_content",
 *   wag_bundle = "department_content",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class DepartmentContent extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_department' => $this->getReferencedEntity($entity->get('field_department')->referencedEntities()),
      'field_document' => $this->getReferencedData($entity->get('field_document')->referencedEntities()),
      'field_event' => $this->getReferencedEntity($entity->get('field_event')->referencedEntities()),
      'field_link_column_1' => $this->generateLinks($entity->get('field_link_column_1')->getValue()),
      'field_link_column_2' => $this->generateLinks($entity->get('field_link_column_2')->getValue()),
      'field_meeting' => $this->getReferencedEntity($entity->get('field_meeting')->referencedEntities()),
      'field_news_content' => $this->getReferencedEntity($entity->get('field_news_content')->referencedEntities()),
      'field_show_meeting_parent_agency' => $this->editFieldValue($entity->get('field_show_meeting_parent_agency')->value, [
        1 => TRUE,
        0 => FALSE,
      ]),
    ];
  }

}
