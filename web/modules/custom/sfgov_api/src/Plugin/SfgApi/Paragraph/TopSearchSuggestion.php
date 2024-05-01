<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_top_search_suggestion",
 *   title = @Translation("Paragraph top_search_suggestion"),
 *   bundle = "top_search_suggestion",
 *   wag_bundle = "top_search_suggestion",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class TopSearchSuggestion extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'link' => $this->generateLinks($entity->get('field_top_search_suggestion_link')->getValue()),
    ];
  }

}
