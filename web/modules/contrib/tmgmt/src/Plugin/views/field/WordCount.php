<?php

namespace Drupal\tmgmt\Plugin\views\field;

use Drupal\tmgmt\JobInterface;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the word count for a job or job item.
 *
 * @ViewsField("tmgmt_wordcount")
 */
class WordCount extends StatisticsBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    if ($entity instanceof JobInterface && $entity->isContinuous()) {
      return;
    }
    return $entity->getWordCount();
  }
}
