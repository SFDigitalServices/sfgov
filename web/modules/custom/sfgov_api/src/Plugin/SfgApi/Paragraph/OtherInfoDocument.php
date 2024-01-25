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
 * )
 */
class OtherInfoDocument extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    // The other_info_document paragraph holds both the entity reference and
    // the title, so we have to jump through some hoops to get the data we need.
    $file_info = $this->getReferencedEntity($entity->get('field_file')->referencedEntities(), TRUE);
    $title = $entity->get('field_title')->value;
    $data = [
      'title' => $title,
      'documents' => [
        'type' => 'document',
        'value' => $file_info[0],
      ],
    ];
    return $data;
  }

}
