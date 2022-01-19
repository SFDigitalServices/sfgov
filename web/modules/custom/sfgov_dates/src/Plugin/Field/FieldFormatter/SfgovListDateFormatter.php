<?php

namespace Drupal\sfgov_dates\Plugin\Field\FieldFormatter;

use Drupal\sfgov_dates\Plugin\Field\FieldFormatter\SfgovDateFormatterBase;

/**
 * Plugin implementation of the 'SfgovListDate' formatter.
 *
 * @FieldFormatter(
 *   id = "sfgov_dates_list_date",
 *   label = @Translation("Sfgov List Date"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SfgovListDateFormatter extends SfgovDateFormatterBase {

  public function setDateString($start_time, $end_time) {
    if ($this->dateRange) {
      $date_string = date('D, F j', $start_time) . ' to ' . date('D, F j', $end_time);
    }
    else {
      $date_string = date('l, F j', $start_time);
    }
    return $date_string;
  }

  public function setTimeString($start_time, $end_time) {
    return FALSE;
  }

}
