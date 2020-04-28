<?php

/**
 * @file
 * Multiple fields remove button API documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify field types for which remove button will be added.
 *
 * @param array $fieldTypes
 *   A list with field types.
 */
function hook_multiple_field_remove_button_field_types_alter(array &$fieldTypes) {
  $fieldTypes[] = "custom_field_type";
}

/**
 * Modify field types for which remove button will not be added.
 *
 * @param array $skipTypes
 *   A list with field types.
 */
function hook_multiple_field_remove_button_skip_types_alter(array &$skipTypes) {
  $skipTypes[] = "custom_field_type";
}

/**
 * Modify field widgets for which remove button will not be added.
 *
 * @param array $skipWidgets
 *   A list with field widgets.
 */
function hook_multiple_field_remove_button_skip_widgets_alter(array &$skipWidgets) {
  $skipWidgets[] = "custom_field_widget";
}

/**
 * Modify field names for which remove button will be added.
 *
 * @param array $includedFields
 *   A list with field names.
 */
function hook_multiple_field_remove_button_field_alter(array &$includedFields) {
  $includedFields = [];
}

/**
 * @} End of "addtogroup hooks".
 */
