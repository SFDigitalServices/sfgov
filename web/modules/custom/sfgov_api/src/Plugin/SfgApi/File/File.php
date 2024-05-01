<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\File;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Plugin implementation of the sfgov_api.
 *
 * @SfgApi(
 *   id = "file_file",
 *   title = @Translation("File (raw)"),
 *   bundle = "file",
 *   wag_bundle = "images",
 *   entity_id = {},
 *   langcode = {},
 *   is_stub = {},
 *   shape = {},
 * )
 */
class File extends SfgApiPluginBase {

  use ApiFieldHelperTrait;
  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  protected $entityType = 'file';

  /**
   *
   */
  public function setCustomData(EntityInterface $entity) {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function setBaseData($entity) {
    $base_data = [];
    if (empty($entity)) {
      return $base_data;
    }
    $file_uri = $entity->getFileUri();
    if (!$file_uri) {
      $message = $this->t('No base file found');
      $this->addPluginError('No file', $message);
    }
    else {
      $file_data = [
        'title' => $entity->get('filename')->value,
        'file' => \Drupal::service('file_system')->realpath($file_uri),
        'fid' => $entity->id(),
      ];
    }
    return $file_data;
  }

}
