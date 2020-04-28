<?php

/**
 * @file
 * Provide views data and handlers for office_hours.module.
 * @TODO: finish upgrade to D7, see advanced help: http://www.example.com/help/views/api-upgrading
 *
 * @ingroup views_module_handlers
 */

/*
function office_hours_field_views_data($field) {
        $data = _office_hours_views_field_views_data($field);
        $db_info = $field['storage'];
        $table_alias = content_views_tablename($field);
        // Swap the filter handler.
        $data[$table_alias][$field['field_name'] . '_day']['filter']['handler'] = 'office_hours_handler_filter_day';
        $data[$table_alias][$field['field_name'] . '_starthours']['filter']['handler'] = 'office_hours_handler_filter_hours';
        $data[$table_alias][$field['field_name'] . '_endhours']['filter']['handler'] = 'content_handler_handler_filter_hours';
        return $data;
}
 */

/**
 * Adding all table field into $data in hook_views_data.
 */
/*
function _office_hours_views_field_views_data($field) {
    $field_types = _content_field_types();
    $db_info = content_database_info($field);

    // Field modules that do not store data in the database
    // should not create views data tables.
    if (empty($db_info['columns'])) {
      return;
    }

    $table_alias = content_views_tablename($field);

    $types = [];
    foreach (field_info_instances() as $type) {
      if (isset($type['fields'][$field['field_name']])) {
        $types[] = $type['name'];
      }
    }

    $data = [];
    $data['table']['group']  = t('Content');
    $data['table']['join']['node'] = [
      'table' => $db_info['table'],
      'left_field' => 'vid',
      'field' => 'vid',
    ];

    $columns = [];
    $arguments = [];
    $filters = [];
    foreach ($db_info['columns'] as $column => $attributes) {
      $columns[] = $attributes['column'];
      $sorts[] = !empty($attributes['sortable']) ? TRUE : FALSE;

      // Identify likely filters and arguments for each column based on field type.
      switch ($attributes['type']) {
        case 'int':
        case 'mediumint':
        case 'tinyint':
        case 'bigint':
        case 'serial':
          $filters[] = 'content_handler_filter_numeric';
          $arguments[] = 'content_handler_argument_numeric';
          break;
        case 'numeric':
        case 'float':
          $filters[] = class_exists('views_handler_filter_float') ? 'content_handler_filter_float' : 'content_handler_filter_numeric';
          $arguments[] = 'content_handler_argument_numeric';
          break;

        case 'text':
        case 'blob':
          // TODO add markup handlers for these types
        default:
          $filters[] = 'content_handler_filter_string';
          $arguments[] = 'content_handler_argument_string';
          break;
      }
    }
    $i=0;

    // Ensure all columns are retrieved,
    $additional_fields = drupal_map_assoc($columns);
    foreach($columns as $key => $column) {
    list(, , $field_type) =explode('_', $column);
    $data[$column] = [
      'group' => t('Content'),
      'title' => t($field_types[$field['type']]['label']) . ': ' . t($field['widget']['label']) . ' (' . $field['field_name'] . ' ' . $field_type . ')',
      'help' =>  t($field_types[$field['type']]['label']) . ' - ' . t('Appears in: @types', ['@types' => implode(', ', $types)]),
      'field' => [
        'field' => $column,
        'table' => $db_info['table'],
        'handler' => 'content_handler_field_multiple',
        'click sortable' => $sorts[$i],
       // 'additional fields' => $additional_fields,
        'content_field_name' => $field['field_name'],
        'allow empty' => TRUE,
        // Access control modules should implement content_views_access_callback().
        'access callback' => 'content_views_access_callback',
        'access arguments' => [$field],
      ],
      'argument' => [
        'field' => $column,
        'table' => $db_info['table'],
        'handler' => $arguments[$i],
        'click sortable' => $sorts[$i], // TODO used in once place in node.views.inc, should we use it here?
        'name field' => '', // TODO
        //'additional fields' => $additional_fields,
        'content_field_name' => $field['field_name'],
        'empty field name' => t('<No value>'),
        'allow empty' => TRUE,
      ],
      'filter' => [
        'field' => $column,
        'title' => t($field['widget']['label']) . ': ' . $field_type,
        'table' => $db_info['table'],
        'handler' => $filters[$i],
        //'additional fields' => $additional_fields,
        'content_field_name' => $field['field_name'],
        'allow empty' => TRUE,
      ],
    ];
    $i++;
    }
    // TODO do we need different handling for sorts with Views 2,
    // especially when relationships are involved?
    if (!empty($sorts[$i])) {
      $data[$column]['sort'] = [
        'field' => $column,
        'table' => $db_info['table'],
        'handler' => 'content_handler_sort',
       // 'additional fields' => $additional_fields,
        'content_field_name' => $field['field_name'],
        'allow empty' => TRUE,
      ];
    }
    // @todo: provide automatic filters, sorts, and arguments
    // for each column, not just the first?
    return [$table_alias => $data];
}
 */
