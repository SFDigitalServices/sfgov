<?php

namespace Drupal\sfgov_api\Plugin\SfgovApi\Paragraph;

use Drupal\sfgov_api\SfgovApiPluginBase;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgovApiParagraphPluginBase extends SfgovApiPluginBase {

  /**
   * {@inheritDoc}
   */
  protected $entityType = 'paragraph';

  /**
   * {@inheritDoc}
   */
  public function setBaseData($paragraph) {
    $base_data = [];
    return $base_data;
  }

}
