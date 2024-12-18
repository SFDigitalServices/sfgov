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
 *   shape = {},
 * )
 */
class Image extends SfgApiMediaBase {

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $referenced_file = $entity->get('field_media_image')->referencedEntities()[0];
    $drupal_direct_path = isset($referenced_file) ? $referenced_file->createFileUrl() : NULL;
    $custom_data = [
      'drupal_indirect_path' => $entity->toUrl()->toString(),
      'drupal_direct_path' => $drupal_direct_path,
    ];
    return $custom_data;
  }

}
