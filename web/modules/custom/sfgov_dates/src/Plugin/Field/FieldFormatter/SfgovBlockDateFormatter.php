<?php

namespace Drupal\sfgov_dates\Plugin\Field\FieldFormatter;

use Drupal\sfgov_dates\Plugin\Field\FieldFormatter\SfgovDateFormatterBase;

/**
 * Plugin implementation of the 'SfgovBlockDate' formatter.
 *
 * @FieldFormatter(
 *   id = "sfgov_dates_block_date",
 *   label = @Translation("Sfgov Block Date"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SfgovBlockDateFormatter extends SfgovDateFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function setDateString($start_time, $end_time) {
    return date('l, M j, Y, g:i a', $start_time);
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeString($start_time, $end_time) {
    return FALSE;
  }
}
