<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Media;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

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
 * )
 */
class Image extends SfgApiMediaBase {

  use ApiFieldHelperTrait;
  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function setCustomData($entity) {
    $referenced_file = $entity->get('field_media_image')->referencedEntities()[0];
    $file_uri = $referenced_file->getFileUri();
    $file_path = \Drupal::service('file_system')->realpath($file_uri);
    $referenced_file->getFilename();

    $custom_data = [
      'title' => $entity->get('name')->value,
      'file' => $file_path,
    ];

    if (!$file_path) {
      $message = $this->t('No base file found for @entity_type of type @bundle with id @entity_id in langcode @langcode', [
        '@entity_type' => $entity->getEntityTypeId(),
        '@bundle' => $this->getBundle(),
        '@entity_id' => $entity->id(),
        '@langcode' => $this->configuration['langcode'],
      ]);
      $this->addPluginError('No file', $message);
    }

    return $custom_data;
  }

}
