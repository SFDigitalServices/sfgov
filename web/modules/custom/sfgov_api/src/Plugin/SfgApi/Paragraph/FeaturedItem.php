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
 *   wag_bundle = "quicklink",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class FeaturedItem extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $link_data = $this->generateLinks($entity->get('field_feature_link')->getvalue())[0]['value'];
    $link_data['description'] = $entity->get('field_description')->value;
    $link_data['title'] = $entity->get('field_feature_title')->value;
    return $link_data;
  }

}
