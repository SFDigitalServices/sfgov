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
 *   is_stub = {},
 * )
 */
class AgendaItem extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // @todo make this a function (also used in otherinfodocument)
    // This is a multivalue reference field so we have to jump through some
    // hoops to make it work.
    $file_ids = $this->getReferencedEntity($entity->get('field_file')->referencedEntities(), TRUE);
    $document_references = [];
    foreach ($file_ids as $file_id) {
      $document_references[] = [
        'type' => 'document',
        'value' => $file_id,
      ];
    }
    return [
      'documents' => $document_references,
      'title_and_text' => [
        'title' => $entity->get('field_title')->value ?: '',
        'text' => $entity->get('field_text_agenda_item')->value ?: '',
      ],
    ];
  }

}
