<?php

namespace Drupal\tmgmt_local\Plugin\views\field;

use Drupal\tmgmt\Plugin\views\field\StatisticsBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the word count for a job or job item.
 *
 * @ViewsField("tmgmt_local_wordcount")
 */
class WordCount extends StatisticsBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\tmgmt_local\LocalTaskInterface $entity */
    $entity = $values->_entity;
    return $entity->getWordCount();
  }

}
