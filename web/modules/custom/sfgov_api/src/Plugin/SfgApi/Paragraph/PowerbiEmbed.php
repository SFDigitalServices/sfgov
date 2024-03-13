<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_powerbi_embed",
 *   title = @Translation("Paragraph powerbi_embed"),
 *   bundle = "powerbi_embed",
 *   wag_bundle = "powerbi_embed",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class PowerbiEmbed extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'alt_text' => $entity->get('field_title')->value,
      'data_notes' => $entity->get('field_data_notes')->value,
      'source_data' => $entity->get('field_source_data')->value,
      'aspect_ratios' => [
        'mobile' => [
          'width' => $entity->get('field_mobile_width')->value,
          'height' => $entity->get('field_mobile_height')->value,
        ],
        'desktop' => [
          'width' => $entity->get('field_desktop_width')->value,
          'height' => $entity->get('field_desktop_height')->value,
        ],
      ],
      'desktop_embed_url' => $entity->get('field_desktop_embed_url')->value,
      'mobile_embed_url' => $entity->get('field_mobile_embed_url')->value,
    ];
  }

}
