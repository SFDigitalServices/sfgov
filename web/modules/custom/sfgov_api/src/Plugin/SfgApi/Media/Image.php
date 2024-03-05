<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Media;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "media_image",
 *   title = @Translation("Media image"),
 *   bundle = "image",
 *   wag_bundle = "images",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 * )
 */
class Image extends SfgApiMediaBase {

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {

    $custom_data = [];
    return $custom_data;
  }

}
