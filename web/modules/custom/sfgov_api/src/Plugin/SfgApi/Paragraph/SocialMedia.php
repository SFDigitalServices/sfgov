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
 *   is_stub = {},
 * )
 */
class SocialMedia extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      'field_facebook' => $this->generateLinks($entity->get('field_facebook')->getvalue()),
      'field_instagram' => $this->generateLinks($entity->get('field_instagram')->getvalue()),
      'field_mastodon' => $this->generateLinks($entity->get('field_mastodon')->getvalue()),
      'field_twitter' => $this->generateLinks($entity->get('field_twitter')->getvalue()),
    ];
  }

}
