<?php

namespace Drupal\tmgmt;

use Drupal\tmgmt\Entity\Job;

/**
 * Represents a job (checkout) queue.
 */
class JobQueue {

  /**
   * Array key to store the contents of $this->queue into the $_SESSION
   * variable.
   *
   * @var string
   */
  protected $session_key = 'tmgmt_job_queue';

  /**
   * An array to hold and manipulate the contents of the job queue.
   *
   * @var array
   */
  protected $queue;

  /**
   * Amount of jobs that have already been processed.
   *
   * @var int
   */
  protected $processed;

  /**
   * The final destination after all jobs have been processed.
   *
   * @var string
   */
  protected $destination;

  /**
   * Set up a new JobItemCart instance.
   *
   * Will load the queue from the session or initialize a new one if nothing has
   * been stored yet.
   */
  public function __construct() {
    if (!isset($_SESSION[$this->session_key])) {
      $_SESSION[$this->session_key] = [
        'queue' => [],
        'processed' => 0,
        'destination' => NULL,
      ];
    }
    $this->queue = &$_SESSION[$this->session_key]['queue'];
    $this->processed = &$_SESSION[$this->session_key]['processed'];
    $this->destination = &$_SESSION[$this->session_key]['destination'];
  }

  /**
   * Initializes the queue with a set of jobs, resets the queue.
   *
   * @param \Drupal\tmgmt\JobInterface[] $jobs
   *   Job jobs to be added.
   * @param string $destination
   *   (optional) A destination to redirect to after the queue is finished.
   */
  public function startQueue(array $jobs, $destination = NULL) {
    $this->resetQueue();
    foreach ($jobs as $job) {
      if (!$this->isJobInQueue($job)) {
        $this->queue[] = $job->id();
      }
    }
    $this->setDestination($destination);
  }

  /**
   * Checks if the source item has been added into the queue.
   *
   *
   * @return bool
   *   If the source item is in the queue.
   */
  public function isJobInQueue(JobInterface $job) {
    return in_array($job->id(), $this->queue);
  }

  /**
   * Remove job jobs from the queue without marking them as processed.
   *
   * @param array $job_ids
   *   Job jobs to be removed.
   */
  public function removeJobs(array $job_ids) {
    $this->queue = array_diff_key($this->queue, $job_ids);
  }

  /**
   * Remove job jobs from the queue without marking them as processed.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   Job to be marked as processed.
   */
  public function markJobAsProcessed(JobInterface $job) {
    if ($this->isJobInQueue($job)) {
      $this->processed++;
    }
    unset($this->queue[array_search($job->id(), $this->queue)]);
  }

  /**
   * Gets jobs in the queue without removing them from the queue.
   *
   * @return \Drupal\tmgmt\JobInterface[] $jobs
   *   Jobs in the queue.
   */
  public function getAllJobs() {
    return Job::loadMultiple($this->queue);
  }

  /**
   * Gets count of remaining jobs in the queue.
   *
   * @return int
   *   Number of jobs in the queue.
   */
  public function count() {
    return count($this->queue);
  }

  /**
   * Returns the next job from the queue.
   *
   * @return \Drupal\tmgmt\JobInterface|null
   *   A job or NULL if the queue is empty.
   */
  public function getNextJob() {
    while ($id = reset($this->queue)) {
      if ($job = Job::load($id)) {
        return $job;
      }
      else {
        // Stale job ID that can't be loaded, remove it from the queue.
        array_shift($this->queue);
      }
    }
  }

  /**
   * Returns URL from the queue.
   *
   * @return \Drupal\Core\Url|null
   *   A URL or NULL if the queue is empty.
   */
  public function getNextUrl() {
    if ($job = $this->getNextJob()) {
      return $job->toUrl();
    }
  }


  /**
   * Remove all contents from the queue.
   */
  public function resetQueue() {
    $this->queue = [];
    $this->processed = 0;
    $this->destination = NULL;
  }

  /**
   * Returns the amount of processed jobs.
   *
   * @return int
   *   Number of processed jobs.
   */
  public function getProcessed() {
    return $this->processed;
  }

  /**
   * Returns the final redirect destination, to be used after all jobs were
   * processed.
   *
   * @return string
   *   The destination.
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * @param string $destination
   */
  public function setDestination($destination) {
    $this->destination = $destination;
  }


}
