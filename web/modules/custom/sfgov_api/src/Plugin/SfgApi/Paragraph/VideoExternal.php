<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_video_external",
 *   title = @Translation("Paragraph video_external"),
 *   bundle = "video_external",
 *   wag_bundle = "video_external",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class VideoExternal extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is incomplete and only exists for entity referencing
      // at the moment.
      'field_description' => $entity->get('field_description')->value,
      'field_link' => $entity->get('field_link')->value,
      'field_text' => $entity->get('field_text')->value,
      'field_title' => $entity->get('field_title')->value,
    ];
  }

}
