<?php

namespace Drupal\viewfield\Plugin\migrate\field\d7;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * ViewField field migration.
 *
 * @MigrateField(
 *   id = "viewfield",
 *   type_map = {
 *     "viewfield" = "viewfield",
 *   },
 *   core = {7},
 *   source_module = "viewfield",
 *   destination_module = "viewfield"
 * )
 */
class ViewField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'viewfield_default' => 'viewfield_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'viewfield_select' => 'viewfield_select',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $sub_process = [
      'view_ids' => [
        'plugin' => 'explode',
        'delimiter' => '|',
        'limit' => 2,
        'source' => 'vname',
      ],
      'target_id' => [
        'plugin' => 'get',
        'source' => '@view_ids/0',
      ],
      'display_id' => [
        'plugin' => 'get',
        'source' => '@view_ids/1',
      ],
      'arguments' => [
        'plugin' => 'get',
        'source' => 'vargs',
      ],
    ];

    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => $sub_process,
    ];

    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
