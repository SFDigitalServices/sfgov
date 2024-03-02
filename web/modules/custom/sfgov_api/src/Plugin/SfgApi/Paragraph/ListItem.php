<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_list_item",
 *   title = @Translation("Paragraph list_item"),
 *   bundle = "list_item",
 *   wag_bundle = "list_item",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class ListItem extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'link' => $this->generateLinks($entity->get('field_link')->getvalue()),
    ];
  }

}
