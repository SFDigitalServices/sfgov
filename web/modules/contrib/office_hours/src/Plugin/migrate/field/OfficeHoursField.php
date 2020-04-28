<?php

namespace Drupal\office_hours\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateCckField(
 *   id = "office_hours_field",
 *   core = {7},
 *   source_module = "office_hours",
 *   destination_module = "office_hours",
 *   type_map = {
 *    "office_hours" = "office_hours"
 *   }
 * )
 */
class OfficeHoursField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'office_hours' => 'office_hours_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'office_hours' => 'office_hours_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'office_hours_field_plugin',
      'source' => $field_name,
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
