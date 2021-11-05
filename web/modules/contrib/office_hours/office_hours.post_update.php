<?php

/**
 * @file
 * Post update functions for Office Hours.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Adds Office Hours 'default value' schema changes in field config in 8.x-1.3.
 */
function office_hours_post_update_implement_office_hours_default_value_config_schema(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'field_config', function (FieldConfigInterface $fieldConfig) {
      if ($fieldConfig->getFieldStorageDefinition()->getType() !== 'office_hours') {
        return FALSE;
      }

      $default_values = $fieldConfig->getDefaultValueLiteral();
      foreach ($default_values as $key => $default_value_row) {
        $default_values[$key]['starthours'] = (int) $default_value_row['starthours'];
        $default_values[$key]['endhours'] = (int) $default_value_row['endhours'];
        $default_values[$key]['day'] = (int) $default_value_row['day'];
        $default_values[$key]['comment'] = (string) $default_value_row['comment'];
      }
      $fieldConfig->setDefaultValue($default_values);
      return TRUE;
    });
}

/**
 * Adds Office Hours 'formatter.settings' schema changes in entity view display config in 8.x-1.3.
 */
function office_hours_post_update_implement_office_hours_entity_view_display_schema(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $entityViewDisplay) {
      $updated = FALSE;
      foreach ($entityViewDisplay->getComponents() as $key => $component) {
        if ($component['type'] === 'office_hours_table') {
          $component['settings']['compress'] = (bool) $component['settings']['compress'];
          $component['settings']['grouped'] = (bool) $component['settings']['grouped'];
          $component['settings']['schema']['enabled'] = (bool) $component['settings']['schema']['enabled'];
          $entityViewDisplay->setComponent($key, $component);
          $updated = TRUE;
        }
      }
      return $updated;
    });
}
