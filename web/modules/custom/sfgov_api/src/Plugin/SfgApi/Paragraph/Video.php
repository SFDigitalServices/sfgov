<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_video",
 *   title = @Translation("Paragraph video"),
 *   bundle = "video",
 *   wag_bundle = "video",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Video extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo this plugin is incomplete and only exists for entity referencing
      // at the moment.
      // 'field_description' => $entity->get('field_description')->value,
      // 'field_text' => $entity->get('field_text')->value,
      'title' => $entity->get('field_title')->value,
      'video_type' => [
        'type' => 'external_link',
        'value' => [
          'url' => $entity->get('field_video')->value,
          'page' => NULL,
          'link_to' => 'url',
          'link_text' => 'Watch Video',
        ],
      ],
      'description' => $entity->get('field_description')->value,
      // 'field_video' => $entity->get('field_video')->value,
    ];
  }

}
