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
 *   wag_bundle = "video",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class VideoExternal extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'title' => $entity->get('field_title')->value,
      'video_type' => [[
        'type' => 'external_link',
        'value' => [
          'url' => $entity->get('field_link')->uri,
          'link_text' => $entity->get('field_link')->title,
        ],
      ],
      ],
      'description' => $entity->get('field_description')->value ?: '',
    ];
  }

}
