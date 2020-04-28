<?php

namespace Drupal\tmgmt;

/**
 * Interface for continuous translators.
 *
 * @ingroup tmgmt_translator
 */
interface ContinuousTranslatorInterface {

  /**
   * Requests the translation of a JobItem.
   *
   * @param JobItemInterface[] $job_items
   *   The JobItem we want to translate.
   */
  public function requestJobItemsTranslation(array $job_items);

}
