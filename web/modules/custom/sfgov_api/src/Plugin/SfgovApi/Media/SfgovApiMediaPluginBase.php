<?php

namespace Drupal\sfgov_api\Plugin\SfgovApi\Media;

use Drupal\sfgov_api\SfgovApiPluginBase;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgovApiMediaPluginBase extends SfgovApiPluginBase {

  /**
   * {@inheritDoc}
   */
  protected $entityType = 'media';

  /**
   * {@inheritDoc}
   */
  public function setBaseData($media) {

    $base_data = [];
    return $base_data;
  }

}
