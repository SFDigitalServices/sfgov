<?php

namespace Drupal\tmgmt_local\Plugin\views\field;

use Drupal\tmgmt\Plugin\views\field\StatisticsBase;
use Drupal\tmgmt_local\LocalTaskInterface;
use Drupal\tmgmt_local\LocalTaskItemInterface;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the progress of a job or job item.
 *
 * @ViewsField("tmgmt_local_progress")
 */
class Progress extends StatisticsBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;

    if (($entity->getEntityTypeId() == 'tmgmt_local_task' && $entity->getStatus() == LocalTaskInterface::STATUS_CLOSED)
    || ($entity->getEntityTypeId() == 'tmgmt_local_task_item' && $entity->getStatus() == LocalTaskItemInterface::STATUS_CLOSED)) {
      return t('Closed');
    }

    $counts = array(
      '@untranslated' => $entity->getCountUntranslated(),
      '@translated' => $entity->getCountTranslated(),
      '@completed' => $entity->getCountCompleted(),
    );

    $title = t('Untranslated: @untranslated, translated: @translated, completed: @completed.', $counts);

    $one_hundred_percent = array_sum($counts);
    if ($one_hundred_percent == 0) {
      return [];
    }

    $output = array(
      '#theme' => 'tmgmt_local_progress_bar',
      '#attached' => array('library' => 'tmgmt/admin'),
      '#title' => $title,
      '#count_untranslated' => $counts['@untranslated'],
      '#count_translated' => $counts['@translated'],
      '#count_completed' => $counts['@completed'],
      '#width_untranslated' => $counts['@untranslated'] / $one_hundred_percent * 100,
      '#width_translated' => $counts['@translated'] / $one_hundred_percent * 100,
      '#width_completed' => $counts['@completed'] / $one_hundred_percent * 100,
    );
    return $output;
  }

}
