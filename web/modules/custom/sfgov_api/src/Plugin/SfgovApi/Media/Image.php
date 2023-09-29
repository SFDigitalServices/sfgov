<?php

namespace Drupal\sfgov_api\Plugin\SfgovApi\Media;

use Drupal\sfgov_api\Plugin\SfgovApi\ApiFieldHelperTrait;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgovApi(
 *   id = "media_image",
 *   title = @Translation("Media Image"),
 *   bundle = "image",
 *   entity_id = {},
 *   langcode = {},
 * )
 */
class Image extends SfgovApiMediaPluginBase {

  use ApiFieldHelperTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $temp = 'temp';
    $referenced_file = $entity->get('field_media_image')->referencedEntities()[0];
    $image_values = $entity->get('field_media_image')[0]->getValue();
    $file_name = $referenced_file->getFilename();
    $wagtail_base_file_path = 'https://media.www-temp.staging.dev.sf.gov/original_images/';

    // @todo All of the comments below are whats in the API. this list needs to be refined.
    return [
    // "https://api.staging.dev.sf.gov/api/cms/images/1",
      'detail_url' => $temp,
      'title' => $entity->get('name')->value,
      'file' => "{$wagtail_base_file_path}{$file_name}",
      'width' => $image_values['width'],
      'height' => $image_values['height'],
      'created_at' => $this->getWagtailTime($referenced_file->get('created')->value),
      'focal_point_x' => NULL,
      'focal_point_y' => NULL,
      'focal_point_width' => NULL,
      'focal_point_height' => NULL,
      'file_size' => $referenced_file->getSize(),
    // 'e347b7f97001d5ed72acddfe0f78489e686ff564',
      'file_hash' => $temp,
    ];

  }

}
