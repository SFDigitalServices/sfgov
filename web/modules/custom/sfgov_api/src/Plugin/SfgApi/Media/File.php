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
 * )
 */
class File extends SfgApiMediaBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $custom_data = [];
    return $custom_data;
  }

}
