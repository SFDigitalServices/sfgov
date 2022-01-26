<?php

namespace Drupal\sfgov_update_fields\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a date_to_smart_date plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: date_to_smart_date
 *     source:
 *      - foo
 *      - bar
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "date_to_smart_date"
 * )
 */
class DateToSmartDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $start_date = $this->getTimestamp($value[0]);

    // This logic is taken from SmartDateDrushCommands.php.
    // Couldnt use that command because it assumes a date range field, this was
    // two separate date fields.
    if (!empty($value[1])) {
      $end_date = $this->getTimestamp($value[1]);
      // If valid end date, set duration. Otherwise make a new end date.
      if ($start_date < $end_date) {
        $duration = round(($end_date - $start_date) / 60);
      }
      else {
        $end_date = NULL;
      }
    }

    if (!$end_date) {
      // If the end date is bogus, use default duration.
      // $default_duration = 60;
      $end_date = $start_date;
      $duration = 60;
    }

    $smart_date = [
      'value' => $start_date,
      'end_value' => $end_date,
      'duration' => $duration,
      'timezone' => '',
    ];

    return $smart_date;
  }

  /**
   * Takes a datetime string and converts it to a timestamp
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $time
   *   The time to be converted.
   *
   * @return int
   *   A timestamp of the datetime.
   */
  public function getTimestamp($time) {
    $utc = new \DateTimeZone('UTC');
    $timestamp = new \DateTime($time, $utc);
    $timestamp = $timestamp->format('U');
    // Remove any seconds from the incoming value.
    $timestamp -= $timestamp % 60;
    return $timestamp;
  }
}
