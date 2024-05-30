<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_other_info_document",
 *   title = @Translation("Paragraph other_info_document"),
 *   bundle = "other_info_document",
 *   wag_bundle = "downloadable_files",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class OtherInfoDocument extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // @todo make this a function (also used in agendaitem)
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
      'title' => $entity->get('field_title')->value,
      'documents' => $document_references,
    ];
  }

}
