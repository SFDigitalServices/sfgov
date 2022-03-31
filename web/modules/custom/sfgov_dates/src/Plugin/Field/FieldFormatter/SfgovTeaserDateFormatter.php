<?php

namespace Drupal\sfgov_dates\Plugin\Field\FieldFormatter;

use Drupal\sfgov_dates\Plugin\Field\FieldFormatter\SfgovDateFormatterBase;

/**
 * Plugin implementation of the 'SfgovTeaserDate' formatter.
 *
 * @FieldFormatter(
 *   id = "sfgov_dates_teaser_date",
 *   label = @Translation("Sfgov Teaser Date"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SfgovTeaserDateFormatter extends SfgovDateFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function setDateString($start_time, $end_time) {
    if ($this->dateRange) {
      $date_string = date('l, F j', $start_time) . ' to ' . date('l, F j', $end_time);
    }
    else {
      $date_string = date('l, F j', $start_time);
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
