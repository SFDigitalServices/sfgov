<?php

namespace Drupal\tmgmt_local;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for tmgmt_local_task_item entity.
 *
 * @ingroup tmgmt_local_task_item
 */
interface LocalTaskItemInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Translation task item is untranslated.
   */
  const STATUS_PENDING = 0;

  /**
   * Translation task item is translated and pending review of the job item.
   */
  const STATUS_COMPLETED = 1;

  /**
   * Translation job item has been rejected and the task needs to be updated.
   */
  const STATUS_REJECTED = 2;

  /**
   * The translation task item has been completed and closed.
   */
  const STATUS_CLOSED = 3;

  /**
   * Returns the translation task.
   *
   * @return \Drupal\tmgmt_local\Entity\LocalTask
   *   The LocalTask.
   */
  public function getTask();

  /**
   * Returns the translation job item.
   *
   * @return \Drupal\tmgmt\JobItemInterface
   *   The JobItem.
   */
  public function getJobItem();

  /**
   * Returns the status of the local task item.
   *
   * Can be one of the local task item status constants.
   *
   * @return int
   *   The status of the local task item.
   */
  public function getStatus();

  /**
   * Returns TRUE if the local task is pending.
   *
   * @return bool
   *   TRUE if the local task item is untranslated.
   */
  public function isPending();

  /**
   * Returns TRUE if the local task is translated (fully translated).
   *
   * @return bool
   *   TRUE if the local task item is translated.
   */
  public function isCompleted();

  /**
   * Returns TRUE if the local task is closed (translated and accepted).
   *
   * @return bool
   *   TRUE if the local task item is translated and accepted.
   */
  public function isClosed();

  /**
   * Sets the task item status to completed.
   */
  public function completed();

  /**
   * Sets the task item status to closed.
   */
  public function closed();

  /**
   * Updates the values for a specific substructure in the data array.
   *
   * The values are either set or updated but never deleted.
   *
   * @param string|array $key
   *   Key pointing to the item the values should be applied.
   *   The key can be either be an array containing the keys of a nested array
   *   hierarchy path or a string with '][' or '|' as delimiter.
   * @param array $values
   *   Nested array of values to set.
   * @param bool $replace
   *   (optional) When TRUE, replaces the structure at the provided key instead
   *   of writing into it.
   */
  public function updateData($key, $values = array(), $replace = FALSE);

  /**
   * Gets translation data.
   *
   * The structure is similar to the form API in the way that it is a possibly
   * nested array with the following properties whose presence indicate that the
   * current element is a text that might need to be translated.
   *
   * - #text: The translated text of the corresponding entry in the job item.
   * - #status: The status of the translation.
   *
   * The key can be an alphanumeric string.
   *
   * @param array $key
   *   If present, only the subarray identified by key is returned.
   * @param string $index
   *   Optional index of an attribute below $key.
   *
   * @return array
   *   A structured data array.
   */
  public function getData($key = array(), $index = NULL);

  /**
   * Gets count of all translated data items.
   *
   * @return int
   *   Translated count
   */
  public function getCountTranslated();

  /**
   * Gets count of all untranslated data items.
   *
   * @return int
   *   Translated count
   */
  public function getCountUntranslated();

  /**
   * Gets count of all completed data items.
   *
   * @return int
   *   Translated count
   */
  public function getCountCompleted();

  /**
   * Recalculates statistical word-data: pending, completed, rejected, closed.
   */
  public function recalculateStatistics();

  /**
   * Gets a labeled list of all available statuses.
   *
   * @return array
   *   A list of all available statuses.
   */
  public static function getStatuses();

}
