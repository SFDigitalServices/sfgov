<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Media;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "media_file",
 *   title = @Translation("Media file"),
 *   bundle = "file",
 *   wag_bundle = "documents",
 *   entity_id = {},
 *   langcode = {},
 *   shape = {},
 * )
 */
class File extends SfgApiMediaBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $custom_data = [
      'description' => $entity->get('field_description')->value ?: '',
      'published_date' => $entity->get('field_published_date')->value ?: NULL,
    ];
    return $custom_data;
  }

}
