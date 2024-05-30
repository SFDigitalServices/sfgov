<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_list",
 *   title = @Translation("Paragraph list"),
 *   bundle = "list",
 *   wag_bundle = "list",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class List extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo incomplete
      'field_content' => $entity->get('field_content')->value,
    ];
  }

}
