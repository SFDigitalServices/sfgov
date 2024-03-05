<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_featured_item",
 *   title = @Translation("Paragraph featured_item"),
 *   bundle = "featured_item",
 *   wag_bundle = "featured_item",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class FeaturedItem extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_description' => $entity->get('field_description')->value,
      'field_feature_link' => $this->generateLinks($entity->get('field_feature_link')->getvalue()),
      'field_feature_title' => $entity->get('field_feature_title')->value,
    ];
  }

}
