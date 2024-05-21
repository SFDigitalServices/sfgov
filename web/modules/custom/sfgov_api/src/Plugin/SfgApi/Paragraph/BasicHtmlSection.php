<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_basic_html_section",
 *   title = @Translation("Paragraph basic_html_section"),
 *   bundle = "basic_html_section",
 *   wag_bundle = "title_and_text",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {},
 *   shape = {},
 * )
 */
class BasicHtmlSection extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'text' => $entity->get('field_description')->value,
      'title' => $entity->get('field_title')->value,
    ];
  }

}
