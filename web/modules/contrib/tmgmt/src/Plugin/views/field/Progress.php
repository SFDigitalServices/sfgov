<?php

namespace Drupal\tmgmt\Plugin\views\field;

use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the progress of a job or job item.
 *
 * @ViewsField("tmgmt_progress")
 */
class Progress extends StatisticsBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);

    // If job is continuous we don't show anything.
    if ($entity instanceof JobInterface && $entity->isContinuous()) {
      return;
    }

    if ($entity instanceof JobInterface) {
      switch ($entity->getState()) {
        case JobInterface::STATE_UNPROCESSED:
          return t('Unprocessed');
          break;

        case JobInterface::STATE_REJECTED:
          return t('Rejected');
          break;

        case JobInterface::STATE_ABORTED:
          return t('Aborted');
          break;

        case JobInterface::STATE_FINISHED:
          return t('Finished');
          break;
      }
    }
    elseif ($entity instanceof JobItemInterface) {
      switch ($entity->getState()) {
        case JobItemInterface::STATE_INACTIVE:
          return t('Inactive');
          break;

        case JobItemInterface::STATE_ACCEPTED:
          return t('Accepted');
          break;

        case JobItemInterface::STATE_ABORTED:
          return t('Aborted');
          break;
      }
    }
    $counts = array(
      '@pending' => $entity->getCountPending(),
      '@translated' => $entity->getCountTranslated(),
      '@reviewed' => $entity->getCountReviewed(),
      '@accepted' => $entity->getCountAccepted(),
    );

    $title = t('Pending: @pending, translated: @translated, reviewed: @reviewed, accepted: @accepted.', $counts);

    $sum = array_sum($counts);
    if ($sum == 0) {
      return [];
    }

    $output = [
      '#theme' => 'tmgmt_progress_bar',
      '#attached' => ['library' => 'tmgmt/admin'],
      '#title' => $title,
      '#entity' => $entity,
      '#total' => $sum,
      '#parts' => [
        'pending' => [
          'count' => $counts['@pending'],
          'width' => $counts['@pending'] / $sum * 100,
        ],
        'translated' => [
          'count' => $counts['@translated'],
          'width' => $counts['@translated'] / $sum * 100,
        ],
        'reviewed' => [
          'count' => $counts['@reviewed'],
          'width' => $counts['@reviewed'] / $sum * 100,
        ],
        'accepted' => [
          'count' => $counts['@accepted'],
          'width' => $counts['@accepted'] / $sum * 100,
        ],
      ],
    ];
    return $output;
  }

}
