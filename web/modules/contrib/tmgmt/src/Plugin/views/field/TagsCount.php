<?php

namespace Drupal\tmgmt\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler which shows the HTML tags count for a job or job item.
 *
 * @ViewsField("tmgmt_tagscount")
 */
class TagsCount extends StatisticsBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    return $entity->getTagsCount();
  }
}
