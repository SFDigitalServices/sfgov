<?php

namespace Drupal\tmgmt_local;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for tmgmt_local_task entity.
 *
 * @ingroup tmgmt_local_task
 */
interface LocalTaskInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Translation task is not assigned to translator.
   */
  const STATUS_UNASSIGNED = 0;

  /**
   * Translation task is pending.
   */
  const STATUS_PENDING = 1;

  /**
   * Translation task is completed (all job items are translated).
   */
  const STATUS_COMPLETED = 2;

  /**
   * Translation task is rejected (at least some job items are rejected).
   */
  const STATUS_REJECTED = 3;

  /**
   * Translation task is closed.
   */
  const STATUS_CLOSED = 4;

  /**
   * Return the user assigned to this task.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The user assigned to this task or NULL if there is no user assigned.
   */
  public function getAssignee();

  /**
   * Return the corresponding translation job.
   *
   * @return \Drupal\tmgmt\JobInterface
   *   The job.
   */
  public function getJob();

  /**
   * Assign translation task to passed user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   User object.
   */
  public function assign(AccountInterface $user);

  /**
   * Unassign translation task.
   */
  public function unassign();

  /**
   * Returns all local task items attached to this task.
   *
   * @param array $conditions
   *   Additional conditions.
   *
   * @return \Drupal\tmgmt_local\Entity\LocalTaskItem[]
   *   An array of local task items.
   */
  public function getItems($conditions = array());

  /**
   * Create a task item for this task and the given job item.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item.
   */
  public function addTaskItem(JobItemInterface $job_item);

  /**
   * Returns the status of the task. Can be one of the task status constants.
   *
   * @return int
   *   The status of the task or NULL if it hasn't been set yet.
   */
  public function getStatus();

  /**
   * Updates the status of the task.
   *
   * @param int $status
   *   The new status of the task. Has to be one of the task status constants.
   * @param string $message
   *   (Optional) The log message to be saved along with the status change.
   * @param array $variables
   *   (Optional) An array of variables to replace in the message on display.
   * @param string $type
   *   (optional) The message type.
   *
   * @return int
   *   The updated status of the task if it could be set.
   *
   * @see Job::addMessage()
   */
  public function setStatus($status, $message = NULL, $variables = array(), $type = 'debug');

  /**
   * Checks whether the passed value matches the current status.
   *
   * @param int $status
   *   The value to check the current status against.
   *
   * @return bool
   *   TRUE if the passed status matches the current status, FALSE otherwise.
   */
  public function isStatus($status);

  /**
   * Checks whether the user described by $account is the author of this task.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (Optional) A user object. Defaults to the currently logged in user.
   */
  public function isAuthor(AccountInterface $account = NULL);

  /**
   * Returns whether the status of this task is 'unassigned'.
   *
   * @return bool
   *   TRUE if the status is 'unassigned', FALSE otherwise.
   */
  public function isUnassigned();

  /**
   * Returns whether the status of this task is 'pending'.
   *
   * @return bool
   *   TRUE if the status is 'pending', FALSE otherwise.
   */
  public function isPending();

  /**
   * Returns whether the status of this task is 'completed'.
   *
   * @return bool
   *   TRUE if the status is 'completed', FALSE otherwise.
   */
  public function isCompleted();

  /**
   * Returns whether the status of this task is 'rejected'.
   *
   * @return bool
   *   TRUE if the status is 'rejected', FALSE otherwise.
   */
  public function isRejected();

  /**
   * Returns whether the status of this task is 'closed'.
   *
   * @return bool
   *   TRUE if the status is 'closed', FALSE otherwise.
   */
  public function isClosed();

  /**
   * Count of all translated data items.
   *
   * @return int
   *   Translated count
   */
  public function getCountTranslated();

  /**
   * Count of all untranslated data items.
   *
   * @return int
   *   Translated count
   */
  public function getCountUntranslated();

  /**
   * Count of all completed data items.
   *
   * @return int
   *   Translated count
   */
  public function getCountCompleted();

  /**
   * Sums up all word counts of this task job items.
   *
   * @return int
   *   The sum of all accepted counts
   */
  public function getWordCount();


  /**
   * Returns loop count of a task.
   *
   * @return int
   *   Task loop count.
   */
  public function getLoopCount();

  /**
   * Increment loop_count property.
   *
   * Does it depending on current status, new status and new assignee.
   *
   * @param int $new_status
   *   New status of task.
   * @param int $new_tuid
   *   New translator uid.
   */
  public function incrementLoopCount($new_status, $new_tuid);

  /**
   * Returns a labeled list of all available statuses.
   *
   * @return array
   *   A list of all available statuses.
   */
  public static function getStatuses();

}
