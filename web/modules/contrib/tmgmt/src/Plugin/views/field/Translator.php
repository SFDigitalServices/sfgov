<?php

namespace Drupal\tmgmt\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the operations for a job.
 *
 * @ViewsField("tmgmt_translator")
 */
class Translator extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\tmgmt\JobInterface $job */
    if ($job = $this->getEntity($values)) {
      return $job->getTranslatorLabel();
    }
    return NULL;
  }

}
