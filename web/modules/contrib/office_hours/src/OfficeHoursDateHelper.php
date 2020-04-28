<?php

namespace Drupal\office_hours;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines lots of helpful functions for use in massaging dates.
 * For formatting options, see http://www.php.net/manual/en/function.date.php
 */
class OfficeHoursDateHelper extends DateHelper {

  /**
   * Gets the day number of first day of the week.
   *
   * @return int
   */
  public static function getFirstDay() {
    return \Drupal::config('system.date')->get('first_day');
  }

  /**
   * Helper function to get the proper format_date() format from the settings.
   *
   * For formatting options, see http://www.php.net/manual/en/function.date.php
   *
   * @param string $time_format
   *
   * @return string
   */
  public static function getTimeFormat($time_format) {
    switch ($time_format) {
      case 'G':
        // 24hr without leading zero.
        $time_format = 'G:i';
        break;

      case 'H':
        // 24hr with leading zero.
        $time_format = 'H:i';
        break;

      case 'g':
        // 12hr ampm without leading zero.
        $time_format = 'g:i a';
        break;

      case 'h':
        // 12hr ampm with leading zero.
        $time_format = 'h:i a';
        break;
    }
    return $time_format;
  }

  /**
   * Pads date parts with zeros.
   *
   * Helper function for a task that is often required when working with dates.
   * Copied from DateTimePlus class.
   *
   * @param int $value
   *   The value to pad.
   * @param int $size
   *   (optional) Size expected, usually 2 or 4. Defaults to 2.
   *
   * @return string
   *   The padded value.
   */
  public static function datePad($value, $size = 2) {
    // return DateTimePlus::datePad($value, $size);
    return sprintf("%0" . $size . "d", $value);
  }

  /**
   * Helper function to convert a time to a given format.
   *
   * For formatting options, see http://www.php.net/manual/en/function.date.php
   *
   * @param $time
   *   Time, in 24hr format '0800', '800', '08:00' or '8:00'
   * @param $time_format
   *   The requested time format.
   * @param bool $end_time
   *   TRUE if the time is an End time of a time slot.
   * @return string
   *   The formatted time.
   */
  public static function format($time, $time_format, $end_time = FALSE) {
    // Convert '800' or '0800' to '08:00'
    if (!strlen($time)) {
      return NULL;
    }
    elseif (!strstr($time, ':')) {
      $time = substr('0000' . $time, -4);
      $hour = substr($time, 0, -2);
      $min = substr($time, -2);
      $time = $hour . ':' . $min;
    }

    $date = new DrupalDateTime($time);
    $formatted_time = $date->format($time_format);

    // Format the 24-hr end time from 0 to 24:00/2400.
    if ($end_time && (strlen($time_format) == strspn($time_format, 'GH:i ')) ) {
      if (($time == '0:00' || $time == '00:00')) {
        $formatted_time = '24:00';
      }
    }
    return $formatted_time;
  }

  /**
   * Formats a time slot.
   *
   * @param string $start
   * @param string $end
   * @param string $format
   * @param string $separator
   *
   * @return string
   */
  public static function formatTimeSlot($start, $end, $format = 'G:i', $separator = ' - ') {
    $start_time = OfficeHoursDateHelper::format($start, $format, FALSE);
    $end_time = OfficeHoursDateHelper::format($end, $format, TRUE);
    return $start_time . $separator . $end_time;
  }

  /**
   * Gets the (limited) hours of a day.
   *
   * Mimics DateHelper::hours() function, but that function does not support limiting
   * the hours. The limits are set in the Widget settings form, and used in the
   * Widget form.
   *
   * {@inheritdoc}
   */
  public static function hours($format = 'H', $required = FALSE, $start = 0, $end = 23) {
    $hours = [];

    // Get the valid hours. DateHelper API doesn't provide a straight method for this.
    $start = ($start == '') ? 0 : max(0, $start);
    $end = ($start == '') ? 23 : min(23, $end);

    // Begin modified copy from date_hours().
    if ($format == 'h' || $format == 'g') {
      // 12-hour format.
      $min = 1;
      $max = 24;
      for ($i = $min; $i <= $max; $i++) {
        if ((($i >= $start) && ($i <= $end)) || ($end - $start >= 11)) {
          $hour = ($i <= 12) ? $i : $i - 12;
          $hours[$hour] = $hour < 10 && ($format == 'H' || $format == 'h') ? "0$hour" : $hour;
        }

      }
      $hours = array_unique($hours);
    }
    else {
      $min = $start;
      $max = $end;
      for ($i = $min; $i <= $max; $i++) {
        $hour = $i;
        $hours[$hour] = $hour < 10 && ($format == 'H' || $format == 'h') ? "0$hour" : $hour;
      }
    }

    $none = ['' => ''];
    $hours = !$required ? $none + $hours : $hours;
    // End modified copy from date_hours().

    return $hours;
  }

  /**
   * Initializes day names, using date_api as key: 0=Sun - 6=Sat.
   * Be careful: date_api uses PHP: 0=Sunday, and DateObject uses ISO: 1=Sunday.
   */
  public static function weekDaysByFormat($format) {
    switch ($format) {
      case 'number':
        // ISO-8601 numerical representation.
        return range(1, 7);
      break;

      case 'none':
        return array_fill(0, 7, '');
      break;

      case 'long':
        return self::weekDays(TRUE);
      break;

      case 'two_letter':
        // @todo: avoid translation from English to XX, in case of Microdata.
        return self::weekDaysAbbr2(TRUE);
      break;

      case 'short': // three-letter;
      default:
        return self::weekDaysAbbr(TRUE);
      break;
    }
  }

  /**
   * @inheritdoc
   */
  public static function weekDaysOrdered($office_hours, $first_day = '') {
    $first_day = ($first_day == '') ? OfficeHoursDateHelper::getFirstDay() : $first_day;
    if ($first_day > 0) {
      for ($i = 1; $i <= $first_day; $i++) {
        $last = array_shift($office_hours);
        array_push($office_hours, $last);
      }
    }
    return $office_hours;
  }

  /**
   * Wrapper function to centralize all Date/Time functions into DateHelper class.
   * Creates a date object from an array of date parts.
   *
   * @param array $date_parts
   * @param int $timezone
   * @param array $settings
   *
   * @return static
   */
  public static function createFromArray(array $date_parts, $timezone = NULL, $settings = []) {
    return DrupalDateTime::createFromArray($date_parts, $timezone, $settings);
  }

  /**
   * Wrapper function to centralize all Date/Time functions into DateHelper class.
   * Creates a date object from an input format.
   */
  public static function createFromFormat($format, $time, $timezone = NULL, $settings = []) {
    return DrupalDateTime::createFromFormat($format, $time, $timezone, $settings);
  }

}
