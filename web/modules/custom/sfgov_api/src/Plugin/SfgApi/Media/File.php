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
    $referenced_file = $entity->get('field_media_file')->referencedEntities()[0];
    $drupal_direct_url = isset($referenced_file) ? $referenced_file->createFileUrl() : NULL;
    $custom_data = [
      'description' => $entity->get('field_description')->value ?: '',
      'published_date' => $entity->get('field_published_date')->value ?: NULL,
      'drupal_indirect_url' => $entity->toUrl()->toString(),
      'drupal_direct_url' => $drupal_direct_url,
    ];
    return $custom_data;
  }

}
