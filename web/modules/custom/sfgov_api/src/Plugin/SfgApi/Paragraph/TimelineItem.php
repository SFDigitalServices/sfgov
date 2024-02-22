<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_timeline_item",
 *   title = @Translation("Paragraph timeline_item"),
 *   bundle = "timeline_item",
 *   wag_bundle = "item",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class TimelineItem extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_headings')->value,
      'text' => $entity->get('field_descriptions')->value,
    ];
  }

}
