<?php

namespace Drupal\office_hours\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Maps D7 office_hours_hours values to office_hours_values values.
 *
 * @MigrateProcessPlugin(
 *   id = "office_hours_field_plugin"
 * )
 */
class OfficeHoursField extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $value;
  }

}
