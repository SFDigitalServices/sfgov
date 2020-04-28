<?php

namespace Drupal\google_analytics_reports\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date;

/**
 * A handler to provide filters for Google Analytics dates.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("google_analytics_date")
 */
class GoogleAnalyticsDate extends Date {

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = [
      '=' => [
        'title' => $this->t('Is equal to'),
        'method' => 'opSimple',
        'short' => $this->t('='),
        'values' => 1,
      ],
    ];
    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    $origin = (!empty($this->value['type']) && $this->value['type'] == 'offset') ? REQUEST_TIME : 0;
    $value = intval(strtotime($this->value['value'], $origin));

    $this->query->addWhere($this->options['group'], $field, $value, $this->operator);
  }

}
