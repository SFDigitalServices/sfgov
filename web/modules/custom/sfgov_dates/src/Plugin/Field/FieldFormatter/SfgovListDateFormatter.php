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
    return 'date' . $start_time;
  }

  public function setTimeString($start_time, $end_time) {
    return 'time' . $start_time;
  }

}
