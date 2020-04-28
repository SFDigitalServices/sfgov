<?php

namespace Drupal\tmgmt;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for the tmgmt_message entity.
 *
 * @ingroup tmgmt_job
 */
interface MessageInterface extends ContentEntityInterface {

  /**
   * Returns the translated message.
   *
   * @return TranslatableMarkup
   *   The message.
   */
  public function getMessage();

  /**
   * Loads the job entity that this job message is attached to.
   *
   * @return \Drupal\tmgmt\JobInterface
   *   The job entity that this job message is attached to or FALSE if there was
   *   a problem.
   */
  public function getJob();

  /**
   * Loads the job entity that this job message is attached to.
   *
   * @return \Drupal\tmgmt\JobItemInterface
   *   The job item entity that this job message is attached to or FALSE if
   *   there was a problem.
   */
  public function getJobItem();

  /**
   * Returns the message type.
   *
   * @return string
   *   Message type.
   */
  public function getType();

}
