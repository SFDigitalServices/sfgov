<?php

/**
 * @file
 * Contains \Drupal\toc_api\TocTypeInterface.
 */

namespace Drupal\toc_api;

/**
 * Provides an interface defining a TOC type.
 */
interface TocTypeInterface {

  /**
   * Returns the TOC type options.
   *
   * @return array
   *   The table of contents options.
   */
  public function getOptions();

}
