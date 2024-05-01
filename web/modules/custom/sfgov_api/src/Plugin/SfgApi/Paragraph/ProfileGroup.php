<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "paragraph_profile_group",
 *   title = @Translation("Paragraph profile_group"),
 *   bundle = "profile_group",
 *   wag_bundle = "profile_group",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class ProfileGroup extends SfgApiParagraphBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    return [
      // @todo incomplete.
      'field_description' => $entity->get('field_description')->value,
      'field_profiles' => $entity->get('field_profiles')->value,
      'field_title' => $entity->get('field_title')->value,
    ];
  }

}
