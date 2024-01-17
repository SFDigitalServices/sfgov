<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Eck;

use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgApiEckBase extends SfgApiPluginBase {

  /**
   * {@inheritDoc}
   */
  protected $entityType = 'eck';

  /**
   * {@inheritDoc}
   */
  public function setBaseData($eck) {
    $base_data = [];
    return $base_data;
  }

}
