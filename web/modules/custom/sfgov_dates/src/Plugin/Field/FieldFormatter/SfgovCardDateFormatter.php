<?php

namespace Drupal\sfgov_dates\Plugin\Field\FieldFormatter;

use Drupal\sfgov_dates\Plugin\Field\FieldFormatter\SfgovDateFormatterBase;

/**
 * Plugin implementation of the 'SfgovCardDate' formatter.
 *
 * @FieldFormatter(
 *   id = "sfgov_dates_card_date",
 *   label = @Translation("Sfgov Card Date"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SfgovCardDateFormatter extends SfgovDateFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function setDateString($start_time, $end_time) {
    if ($this->dateRange) {
      $date_string = date('D, M j', $start_time) . ' to ' . date('D, M j', $end_time);
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
    return FALSE;
  }

}
