<?php

namespace Drupal\sfgov_media\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;

/**
 * Power BI entity media source.
 *
 * @MediaSource(
 *   id = "power_bi",
 *   label = @Translation("Power BI"),
 *   allowed_field_types = {"string", "string_long"},
 *   description = @Translation("Provides media source type for Power BI."),
 * )
 */
class PowerBi extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'url' => $this->t('The embed URL'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $embed_url = $this->getMediaUrl($media);

    if (!$embed_url) {
      return [];
    }

    switch ($attribute_name) {
      case 'embed_url':
        return $embed_url;
    }

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * Returns the embed url from the source_url_field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string|bool
   *   The embed url from the source_url_field if found. False otherwise.
   */
  protected function getMediaUrl(MediaInterface $media) {
    /** @var \Drupal\media\MediaTypeInterface $media_type */
    $media_type = $this->entityTypeManager
      ->getStorage('media_type')
      ->load($media->bundle());
    $source_field = $this->getSourceFieldDefinition($media_type);
    $field_name = $source_field->getName();

    if (!$media->hasField($field_name)) {
      return FALSE;
    }

    $property_name = $source_field->getFieldStorageDefinition()->getMainPropertyName();
    return $media->{$field_name}->{$property_name};
  }

}
