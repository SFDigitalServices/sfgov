<?php

namespace Drupal\sfgov_api\Plugin\SfgApi\Paragraph;

use Drupal\sfgov_api\SfgApiPluginBase;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgApiParagraphBase extends SfgApiPluginBase {

  /**
   * {@inheritDoc}
   */
  protected $entityType = 'paragraph';

  /**
   * {@inheritDoc}
   */
  public function setBaseData($paragraph) {
    $this->checkNullFields();
    $base_data = [];
    return $base_data;
  }

  public function checkNullFields() {
    $derp = true;
  }

}
