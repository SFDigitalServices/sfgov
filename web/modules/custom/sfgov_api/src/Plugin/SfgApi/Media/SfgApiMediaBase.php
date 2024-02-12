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
    $base_data = [];
    $file_data = $this->getReferencedFile($media);
    if ($file_data) {
      if (!$file_data['path']) {
        $message = $this->t('No base file found for media of type @bundle with id @entity_id in langcode @langcode', [
          '@bundle' => $this->getBundle(),
          '@entity_id' => $media->id(),
          '@langcode' => $this->configuration['langcode'],
        ]);
        $this->addPluginError('No file', $message);
      }
      else {
        $base_data = [
          'title' => $media->get('name')->value,
          'file' => $file_data['path'],
          'fid' => $file_data['fid'],
          // @todo , remove once we have a better source for alt text.
          'alt_text' => 'temp',
        ];
    }
    }

    return $base_data;
  }

  /**
   * Get the referenced file from the media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return array
   *   The referenced file.
   */
  public function getReferencedFile($media) {
    $file_field_name = '';
    $bundle = $media->bundle();
    switch ($bundle) {
      case 'file':
        $file_field_name = 'field_media_file';
        break;

      case 'image':
        $file_field_name = 'field_media_image';
        break;
    }

    $file_data = [];
    $referenced_file = $media->get($file_field_name)->referencedEntities()[0];
    if (isset($referenced_file)) {
      $file_uri = $referenced_file->getFileUri();

      $file_data = [
        'name' => $referenced_file->getFilename(),
        'path' => \Drupal::service('file_system')->realpath($file_uri),
        'fid' => $referenced_file->id(),
      ];
    }

    return $file_data;
  }

}
