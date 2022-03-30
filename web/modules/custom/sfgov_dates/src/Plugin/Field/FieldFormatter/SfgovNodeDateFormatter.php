<?php

namespace Drupal\sfgov_dates\Plugin\Field\FieldFormatter;

use Drupal\sfgov_dates\Plugin\Field\FieldFormatter\SfgovDateFormatterBase;

/**
 * Plugin implementation of the 'SfgovNodeDate' formatter.
 *
 * @FieldFormatter(
 *   id = "sfgov_dates_node_date",
 *   label = @Translation("Sfgov Node Date"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SfgovNodeDateFormatter extends SfgovDateFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function setDateString($start_time, $end_time) {
    if ($this->dateRange) {
      $date_string = date('D, F j', $start_time) . ' to ' . date('D, F j, Y', $end_time);
    }
    else {
      $date_string = date('l, F j, Y', $start_time);
    }
    return $date_string;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeString($start_time, $end_time) {
    if ($this->timeRange) {
      $time_string = date('g:i a', $start_time) . ' to ' . date('g:i a', $end_time);
    }
    else {
      $time_string = date('g:i a', $start_time);
    }
    return $time_string;
  }

}
