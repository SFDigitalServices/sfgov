<?php

namespace Drupal\tmgmt\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Base class for tmgmt fields.
 *
 * @ingroup views_field_handlers
 */
class StatisticsBase extends FieldPluginBase {

  /**
   * Prefetch statistics for all jobs.
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // In case of jobs or tasks, pre-fetch the statistics in a single query and
    // add them to the static cache.
    if ($this->getEntityType() == 'tmgmt_job') {
      $tjids = array();
      foreach ($values as $value) {
        // Skip loading data for continuous jobs.
        if ($this->getEntity($value)->isContinuous()) {
          continue;
        }
        $tjids[] = $this->getValue($value);
      }
      tmgmt_job_statistics_load($tjids);
    }
    elseif ($this->getEntityType() == 'tmgmt_task') {
      $tltids = array();
      foreach ($values as $value) {
        $tltids[] = $value->tjid;
      }
      tmgmt_local_task_statistics_load($tltids);
    }
  }
}
