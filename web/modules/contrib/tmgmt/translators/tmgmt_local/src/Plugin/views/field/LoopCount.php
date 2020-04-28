<?php

namespace Drupal\tmgmt_local\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the word count for a job or job item.
 *
 * @ViewsField("tmgmt_local_loopcount")
 */
class LoopCount extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\tmgmt_local\LocalTaskInterface $entity */
    $entity = $values->_entity;
    return $entity->getLoopCount();
  }

}
