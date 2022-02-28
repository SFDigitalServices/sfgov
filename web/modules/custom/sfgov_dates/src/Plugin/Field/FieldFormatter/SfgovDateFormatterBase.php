<?php

namespace Drupal\sfgov_dates\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

abstract class SfgovDateFormatterBase extends FormatterBase {

  /**
   * Indicates if the date field is set to "all day".
   */
  protected $allDay = FALSE;

  /**
   * Indicates if the date field has a range of times.
   */
  protected $timeRange = FALSE;

  /**
   * Indicates if the date field has a range of dates.
   */
  protected $dateRange = FALSE;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $start_time = $item->value;
      $end_time = $item->end_value;
      $this->isDateRange($start_time, $end_time);
      $this->isTimeRange($start_time, $end_time);
      $element[$delta] = [
        '#date' => $this->setDateString($start_time, $end_time),
        '#time' => (!$this->allDay) ? $this->setTimeString($start_time, $end_time) : FALSE,
        '#start_timestamp' => $start_time,
        '#end_timestamp' => $end_time,
      ];
    }

    return $element;
  }

  /**
   * Determines if the date field has a range of dates.
   *
   * @param timestamp $start_time
   *   The start time.
   *
   * @param timestamp $end_time
   *   The end time.
   *
   */
  protected function isDateRange($start_time, $end_time = NULL) {
    if (date('Y-m-d', $end_time) != date('Y-m-d', $start_time)) {
      $this->dateRange = TRUE;
    }
  }

  /**
   * Determines if the date field has a range of times, and if the field is
   * marked as "all day"
   *
   * @param timestamp $start_time
   *   The start time.
   *
   * @param timestamp $end_time
   *   The end time.
   *
   */
  protected function isTimeRange($start_time, $end_time = NULL) {
    $start_time = date('h:i a', $start_time);
    $end_time = date('h:i a', $end_time);
    if ($start_time != $end_time) {
      // If you mark it as "all day" the smart_date saves the time values as
      // 11:59pm - 12:00am.
      if ($start_time === '12:00 am' && $end_time === '11:59 pm') {
        $this->allDay = TRUE;
      } else {
        $this->timeRange = TRUE;
      }
    }
  }

  /**
   * Formats a date string.
   *
   * @param timestamp $start_time
   *   The start time.
   *
   * @param timestamp $end_time
   *   The end time.
   *
   * @return string
   *   The formatted date string.
   */
  abstract protected function setDateString($start_time, $end_time);

  /**
   * Formats a time string.
   *
   * @param timestamp $start_time
   *   The start time.
   *
   * @param timestamp $end_time
   *   The end time.
   *
   * @return string
   *   The formatted time string.
   */
  abstract protected function setTimeString($start_time, $end_time);

}

