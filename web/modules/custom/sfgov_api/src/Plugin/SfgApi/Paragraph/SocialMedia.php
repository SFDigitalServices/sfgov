<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_social_media",
 *   title = @Translation("Paragraph social_media"),
 *   bundle = "social_media",
 *   wag_bundle = "social_media",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class SocialMedia extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $fields = [
      'field_facebook',
      'field_instagram',
      'field_mastodon',
      'field_twitter',
    ];

    $data = [];
    foreach ($fields as $field) {
      if ($entity->get($field)->getvalue()) {
        $data[] = [
          'type' => explode('field_', $field)[1],
          'value' => $entity->get($field)->getvalue()[0]['uri'] ?: '',
        ];
      }
    }

    return [
      'social_media' => $data,
    ];
  }

}
