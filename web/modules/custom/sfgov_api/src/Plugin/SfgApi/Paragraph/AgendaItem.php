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
      'field_text_agenda_item' => $entity->get('field_text_agenda_item')->value,
      'field_title' => $entity->get('field_title')->value,
    ];
  }

}
