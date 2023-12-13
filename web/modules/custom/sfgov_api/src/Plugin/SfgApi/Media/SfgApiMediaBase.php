<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Media;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgApiMediaBase extends SfgApiPluginBase {

  use ApiFieldHelperTrait;
  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  protected $entityType = 'media';

  /**
   * {@inheritDoc}
   */
  public function setBaseData($media) {
    $referenced_file = $media->get('field_media_image')->referencedEntities()[0];
    $file_uri = $referenced_file->getFileUri();
    $file_path = \Drupal::service('file_system')->realpath($file_uri);
    $referenced_file->getFilename();

    $base_data = [
      'title' => $media->get('name')->value,
      'file' => $file_path,
    ];

    if (!$file_path) {
      $message = $this->t('No base file found for media of type @bundle with id @entity_id in langcode @langcode', [
        '@bundle' => $this->getBundle(),
        '@entity_id' => $media->id(),
        '@langcode' => $this->configuration['langcode'],
      ]);
      $this->addPluginError('No file', $message);
    }
    return $base_data;
  }

}
