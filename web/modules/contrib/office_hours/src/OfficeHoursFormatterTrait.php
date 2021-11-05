<?php

namespace Drupal\office_hours;

use Drupal\Component\Utility\Html;
use Drupal\office_hours\Element\OfficeHoursDatetime;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Factors out OfficeHoursItemList->getItems()->getRows().
 */
trait OfficeHoursFormatterTrait {

  /**
   * Returns the items of a field.
   *
   * @param array $items
   *   Result from FieldItemListInterface $items->getValue().
   * @param array $settings
   * @param array $field_settings
   * @param $time
   *
   * @return array
   *   The formatted list of slots.
   */
  public function getRows($items, array $settings, array $field_settings, $time = NULL) {

    // Initialize days and times, using date_api as key (0=Sun, 6-Sat)
    // Empty days are not yet present in $items, and are now added in $days.
    $office_hours = [];
    for ($day = 0; $day < 7; $day++) {
      $office_hours[$day] = [
        'startday' => $day,
        'endday' => NULL,
        'closed' => $this->t(Html::escape($settings['closed_format'])),
        'current' => FALSE,
        'next' => FALSE,
        'slots' => [],
        'formatted_slots' => [],
        'comments' => [],
      ];
    }

    // Loop through all lines.
    // Detect the current line and the open/closed status.
    // Convert the day_number to (int) to get '0' for Sundays, not 'false'.
    $time = ($time === NULL) ? \Drupal::time()->getRequestTime() : $time;
    $today = (int) idate('w', $time); // Get day_number sun=0 - sat=6.
    $now = date('Hi', $time); // 'Hi' format, with leading zero (0900).
    foreach ($items as $key => $item) {
      // Calculate start and end times.
      $day = (int) $item['day'];
      $office_hours[$day]['closed'] = NULL;
      $office_hours[$day]['slots'][] = [
        // Format to 'Hi' format, with leading zero (0900).
        'start' => OfficeHoursDatetime::get($item['starthours'], 'Hi'),
        'end' => OfficeHoursDatetime::get($item['endhours'], 'Hi'),
        'comment' => $item['comment'],
      ];
    }

    $next = NULL;
    foreach ($office_hours as $day => &$day_data) {
      foreach ($day_data['slots'] as $slot_id => $slot) {
        if ($day <= $today) {
          // Initialize to first day of (next) week, in case we're closed
          // the rest of the week.
          // @todo Use $settings['office_hours_first_day'] ?
          if ($next === NULL) {
            $next = $day;
          }
        }

        if ($day == $today) {
          $start = $slot['start'];
          $end = $slot['end'];
          if ($start > $now) {
            // We will open later today.
            $next = $day;
          }
          elseif (($start < $end) && ($end < $now)) {
            // We were open today, but are already closed.
          }
          else {
            // We are still open.
            $day_data['current'] = TRUE;
            $next = $day;
          }
        }
        elseif ($day > $today) {
          if ($next === NULL) {
            $next = $day;
          }
          elseif ($next < $today) {
            $next = $day;
          }
          else {
            // Just for analysis.
          }
        }
        else {
          // Just for analysis.
        }
      }
    }

    if ($next !== NULL) {
      $office_hours[$next]['next'] = TRUE;
    }

    /*
     * We have a list of all possible rows, marking the next and current day.
     * Now, filter according to formatter settings.
     */

    // Reorder weekdays to match the first day of the week, using formatter settings.
    $office_hours = OfficeHoursDateHelper::weekDaysOrdered($office_hours, $settings['office_hours_first_day']);
    // Compress all slots of the same day into one item.
    if ($settings['compress']) {
      $office_hours = $this->compressSlots($office_hours);
    }
    // Group identical, consecutive days into one item.
    if ($settings['grouped']) {
      $office_hours = $this->groupDays($office_hours);
    }

    // From here, no more adding/removing, only formatting.
    // Format the day names.
    $office_hours = $this->formatLabels($office_hours, $settings);
    // Format the start and end time into one slot.
    $office_hours = $this->formatSlots($office_hours, $settings, $field_settings);

    // Return the filtered days/slots/items/rows.
    switch ($settings['show_closed']) {
      case 'open':
        $office_hours = $this->keepOpenDays($office_hours);
        break;

      case 'next':
        $office_hours = $this->keepNextDay($office_hours);
        break;

      case 'none':
        $office_hours = [];
        break;

      case 'current':
        $office_hours = $this->keepCurrentDay($office_hours);
        break;
    }
    return $office_hours;
  }

  /**
   * Formatter: compress the slots: E.g., 0900-1100 + 1300-1700 = 0900-1700.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function compressSlots(array $office_hours) {
    foreach ($office_hours as &$info) {
      if (is_array($info['slots']) && !empty($info['slots'])) {
        // Initialize first slot of the day.
        $compressed_slot = $info['slots'][0];
        // Compress other slot in first slot.
        foreach ($info['slots'] as $index => $slot) {
          $compressed_slot['start'] = min($compressed_slot['start'], $slot['start']);
          $compressed_slot['end'] = max($compressed_slot['end'], $slot['end']);
        }
        $info['slots'] = [0 => $compressed_slot];
      }
    }
    return $office_hours;
  }

  /**
   * Formatter: group days with same slots into 1 line.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function groupDays(array $office_hours) {
    $times = [];
    for ($i = 0; $i < 7; $i++) {
      if ($i == 0) {
        $times = $office_hours[$i]['slots'];
      }
      elseif ($times != $office_hours[$i]['slots']) {
        $times = $office_hours[$i]['slots'];
      }
      else {
        // N.B. for 0=Sundays, we need to (int) the indices.
        $office_hours[$i]['endday'] = $office_hours[(int) $i]['startday'];
        $office_hours[$i]['startday'] = $office_hours[(int) $i - 1]['startday'];
        $office_hours[$i]['current'] = $office_hours[(int) $i]['current'] || $office_hours[(int) $i - 1]['current'];
        $office_hours[$i]['next'] = $office_hours[(int) $i]['next'] || $office_hours[(int) $i - 1]['next'];
        unset($office_hours[(int) $i - 1]);
      }
    }
    return $office_hours;
  }

  /**
   * Formatter: remove closed days, keeping open days.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function keepOpenDays(array $office_hours) {
    $new_office_hours = [];
    foreach ($office_hours as $day => $info) {
      if (!empty($info['slots'])) {
        $new_office_hours[] = $info;
      }
    }
    return $new_office_hours;
  }

  /**
   * Formatter: remove all days, except the first open day.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function keepNextDay(array $office_hours) {
    $new_office_hours = [];
    foreach ($office_hours as $day => $info) {
      if ($info['current'] || $info['next']) {
        $new_office_hours[$day] = $info;
      }
    }
    return $new_office_hours;
  }

  /**
   * Formatter: remove all days, except for today.
   *
   * @param array $office_hours
   *   Office hours array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function keepCurrentDay(array $office_hours) {
    $new_office_hours = [];

    // Get day_number sun=0 - sat=6.
    $today = (int) idate('w', $_SERVER['REQUEST_TIME']);

    foreach ($office_hours as $info) {
      if ($info['startday'] == $today) {
        $new_office_hours[$today] = $info;
      }
    }
    return $new_office_hours;
  }

  /**
   * Formatter: format the day name.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param array $settings
   *   User settings array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function formatLabels(array $office_hours, array $settings) {
    $day_format = $settings['day_format'];
    $day_names = OfficeHoursDateHelper::weekDaysByFormat($settings['day_format']);
    $group_separator = $settings['separator']['grouped_days'];
    $days_suffix = $settings['separator']['day_hours'];

    foreach ($office_hours as $key => &$info) {
      if ($day_format == 'none') {
        $info['label'] = '';
        continue;
      }

      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $day_names[$info['startday']];
      if (isset($info['endday'])) {
        $label .= $group_separator . $day_names[$info['endday']];
      }
      $info['label'] = $label ? $label . $days_suffix : '';
    }
    return $office_hours;
  }

  /**
   * Formatter: format the office hours list.
   *
   * @param array $office_hours
   *   Office hours array.
   * @param array $settings
   *   User settings array.
   * @param array $field_settings
   *   User field settings array.
   *
   * @return array
   *   Reformatted office hours array.
   */
  protected function formatSlots(array $office_hours, array $settings, array $field_settings) {
    $time_format = OfficeHoursDateHelper::getTimeFormat($settings['time_format']);
    $time_separator = $settings['separator']['hours_hours'];
    $slot_separator = $settings['separator']['more_hours'];
    foreach ($office_hours as &$day_data) {
      $day_data['formatted_slots'] = [];
      $day_data['comments'] = [];
      $has_comment = FALSE;
      foreach ($day_data['slots'] as $key => &$slot_data) {
        $formatted_slot = OfficeHoursDateHelper::formatTimeSlot(
          $slot_data['start'],
          $slot_data['end'],
          $time_format,
          $time_separator
        );
        // Store the formatted slot in the slot itself.
        $slot_data['formatted_slot'] = $formatted_slot;
        // Store the arrays of formatted slots & comments in the day.
        $day_data['formatted_slots'][] = $formatted_slot;
        // Always add comment to keep aligned with time slot.
        $day_data['comments'][] = $slot_data['comment'];
        // Check contents, to avoid unnecessary $slot_parameter.
        $has_comment |= !empty($slot_data['comment']);
      }

      $day_data['formatted_slots'] = empty($day_data['formatted_slots'])
        ? $day_data['closed']
        : implode($slot_separator, $day_data['formatted_slots']);

      if ($has_comment && ($field_settings['comment'] == 2)) {
        // Escape and Translate the comments.
        $day_data['comments'] = array_map('Drupal\Component\Utility\Html::escape', $day_data['comments']);
        $day_data['comments'] = array_map('t', $day_data['comments']);
      }
      $day_data['comments'] = ($has_comment && $field_settings['comment'])
        ? implode($slot_separator, $day_data['comments'])
        : '';
    }
    return $office_hours;
  }

}
