<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Media;

use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgApiMediaBase extends SfgApiPluginBase {

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
