<?php

namespace Drupal\tmgmt\Plugin\views\field;

use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the link for translating translation task items.
 *
 * @ViewsField("tmgmt_job_item_state")
 */
class JobItemState extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\tmgmt\JobItemInterface $job_item */
    $job_item = $this->getEntity($values);
    return $job_item->getStateIcon();
  }

}
