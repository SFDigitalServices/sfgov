<?php

namespace Drupal\tmgmt\Events;

/**
 * Events related to continuous jobs.
 */
final class ContinuousEvents {

  /**
   * This event allows to control whether a job item should be added to a job.
   *
   * @var string
   */
  const SHOULD_CREATE_JOB = 'tmgmt.should_create_job';

}
