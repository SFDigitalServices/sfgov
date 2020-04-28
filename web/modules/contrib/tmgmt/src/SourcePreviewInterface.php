<?php

namespace Drupal\tmgmt;

/**
 * Interface for source plugin controllers they may be previewed.
 *
 * @package Drupal\tmgmt
 */
interface SourcePreviewInterface {

  /**
   * Returns preview url if preview is supported.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   Job item.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  public function getPreviewUrl(JobItemInterface $job_item);

}
