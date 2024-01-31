<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_agenda_item",
 *   title = @Translation("Paragraph agenda_item"),
 *   bundle = "agenda_item",
 *   wag_bundle = "agenda_item",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class AgendaItem extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // 'field_file' => $entity->get('field_file')->value,
      'documents' => $this->getReferencedEntity($entity->get('field_file')->referencedEntities(), TRUE, TRUE),
      'title_and_text' => [
        'title' => $entity->get('field_title')->value,
        'text' => $entity->get('field_text_agenda_item')->value,
      ],
    ];
  }

}
