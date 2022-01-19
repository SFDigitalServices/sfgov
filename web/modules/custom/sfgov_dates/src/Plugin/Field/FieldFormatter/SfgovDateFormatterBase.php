<?php

namespace Drupal\sfgov_dates\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

abstract class SfgovDateFormatterBase extends FormatterBase {

  protected $allDay = FALSE;

  protected $timeRange = FALSE;

  protected $dateRange = FALSE;

  abstract protected function setDateString($start_time, $end_time);

  abstract protected function setTimeString($start_time, $end_time);

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
      $date_string = $this->setDateString($start_time, $end_time);
      $time_string = (!$this->allDay) ? $this->setTimeString($start_time, $end_time) : FALSE;
      $element[$delta] = [
        '#date' => $date_string,
        '#time' => $time_string,
      ];
    }

    return $element;
  }

  protected function isDateRange($start_time, $end_time = NULL) {
    if (date('Y-m-d', $end_time) != date('Y-m-d', $start_time)) {
      $this->dateRange = TRUE;
    }
  }

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

}

