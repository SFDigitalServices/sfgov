<?php

namespace Drupal\office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Represents an Office hours field.
 */
class OfficeHoursItemList extends FieldItemList implements OfficeHoursItemListInterface {

  /**
   * @inheritdoc
   */
  public function isOpen($time = NULL) {

    // Loop through all lines.
    // Detect the current line and the open/closed status.
    // Convert the day_number to (int) to get '0' for Sundays, not 'false'.
    $time = ($time === NULL) ? \Drupal::time()->getRequestTime() : $time;
    $today = (int) idate('w', $time); // Get day_number sun=0 - sat=6.
    $now = date('Gi', $time); // 'Gi' format, with leading zero (0900).
    $is_open = FALSE;
    foreach ($this->getValue() as $key => $item) {
      // Calculate start and end times.
      $day = (int) $item['day'];
      // 'Gi' format, with leading zero (0900).
      $start = OfficeHoursDateHelper::datePad($item['starthours'], 4);
      $end = OfficeHoursDateHelper::datePad($item['endhours'], 4);

      if ($day - $today == -1 || ($day - $today == 6)) {
        // We were open yesterday evening, check if we are still open.
        if ($start >= $end && $end >= $now) {
          $is_open = TRUE;
        }
      }
      elseif ($day == $today) {
        if ($start <= $now) {
          // We were open today, check if we are still open.
          if (($start > $end)    // We are open until after midnight.
            || ($start == $end) // We are open 24hrs per day.
            || (($start < $end) && ($end > $now))
          ) {
            $is_open = TRUE;
          }
        }
      }
    }

    return $is_open;
  }

  /**
   * @inheritdoc
   */
  public function getRows(array $settings, array $field_settings, $time = NULL) {

    // Initialize days and times, using date_api as key (0=Sun, 6-Sat)
    // Empty days are not yet present in $items, and are now added in $days.
    $office_hours = [];
    for ($day = 0; $day < 7; $day++) {
      $office_hours[$day] = [
        'startday' => $day,
        'endday' => NULL,
        'closed' => $this->t($settings['closed_format']),
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
    $now = date('Gi', $time); // 'Gi' format, with leading zero (0900).
    foreach ($this->getValue() as $key => $item) {
      // Calculate start and end times.
      $day = (int) $item['day'];
      // 'Gi' format, with leading zero (0900).
      $start = OfficeHoursDateHelper::datePad($item['starthours'], 4);
      $end = OfficeHoursDateHelper::datePad($item['endhours'], 4);

      $office_hours[$day]['closed'] = NULL;
      $office_hours[$day]['slots'][] = [
        'start' => $start,
        'end' => $end,
        'comment' => $item['comment'],
      ];
    }

    $next = NULL;
    foreach ($office_hours as $day => &$day_data) {
      foreach ($day_data['slots'] as $slot_id => $slot) {
        if ($day <= $today) {
          // Initialize to first day of (next) week, in case we're closed
          // the rest of the week.
          // if ($today == $settings['office_hours_first_day']) {
          if ($day == 0) {
            // Not for first day of the week.
          }
          elseif ($next === NULL) {
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
          else {
            // We were open today, check if we are still open.
            if (($start < $end) && ($end > $now)) {
              // We have already closed.
            }
            else {
              // We are still open.
              $day_data['current'] = TRUE;
              $next = $day;
            }
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

    // We have a list of all possible rows, marking the next and current day.
    // Now, filter according to formatter settings.

    // Reorder weekdays to match the first day of the week, using formatter settings;
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
      case 'all':
        // $office_hours = $office_hours;
        break;
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
      default:
        // $office_hours = $office_hours;
        break;
    }
    return $office_hours;
  }

  /**
   * Compress the slots:
   *   E.g., 0900-1100 + 1300-1700 = 0900-1700
   *
   * @param array $office_hours
   * @return array $office_hours
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

  protected function keepOpenDays(array $office_hours) {
    $new_office_hours = [];
    foreach ($office_hours as $day => $info) {
      if (!empty($info['slots'])) {
        $new_office_hours[] = $info;
      }
    }
    return $new_office_hours;
  }

  protected function keepNextDay(array $office_hours) {
    $new_office_hours = [];
    foreach ($office_hours as $day => $info) {
      if ($info['current'] || $info['next']) {
        $new_office_hours[$day] = $info;
      }
    }
    return $new_office_hours;
  }

  protected function keepCurrentDay(array $office_hours) {
    $new_office_hours = [];

    $today = (int) idate('w', $_SERVER['REQUEST_TIME']); // Get day_number sun=0 - sat=6.

    foreach ($office_hours as $day => $info) {
      if ($day == $today) {
        $new_office_hours[$day] = $info;
      }
    }
    return $new_office_hours;
  }

  protected function formatLabels(array $office_hours, array $settings) {
    $day_names = OfficeHoursDateHelper::weekDaysByFormat($settings['day_format']);
    $group_separator = $settings['separator']['grouped_days'];
    $days_suffix = $settings['separator']['day_hours'];

    foreach ($office_hours as $key => &$info) {
      /* @var $label \Drupal\Core\StringTranslation\TranslatableMarkup */
      $label = $day_names[$info['startday']];
      if (isset($info['endday'])) {
        $label .= $group_separator . $day_names[$info['endday']];
      }
      $info['label'] = $label . $days_suffix;
    }
    return $office_hours;
  }

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
      $day_data['comments'] = $has_comment && $field_settings['comment'] ? implode($slot_separator, $day_data['comments']) : '';
    }
    return $office_hours;
  }

}
