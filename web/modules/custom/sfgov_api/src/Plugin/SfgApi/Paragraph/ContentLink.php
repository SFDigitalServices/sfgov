<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_content_link",
 *   title = @Translation("Paragraph content_link"),
 *   bundle = "content_link",
 *   wag_bundle = "content_link",
 *   entity_id = {},
 *   langcode = {},
 *   referenced_plugins = {},
 * )
 */
class ContentLink extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $derp = true;
    return [
      'text' => $entity->get('field_button_text')->value,
      'content_type' => $entity->get('field_content_type')->value,
      'link' => $this->generateLinks($entity->get('field_link')->getValue()),
    ];
  }

}
