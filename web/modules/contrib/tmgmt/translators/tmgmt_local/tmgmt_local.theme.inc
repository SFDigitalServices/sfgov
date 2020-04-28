<?php

/**
 * @file
 * Theme file stub for tmgmt.
 */

use Drupal\Component\Render\FormattableMarkup;

/**
 * Render one single data item as a table row.
 */
function tmgmt_local_translation_form_element($variables) {
  // Theme table which contains source, translation and action state button.
  $element = $variables['element'];

  $parts = explode('|', $element['#parents'][0]);
  $header_title = ucfirst(str_replace('_', ' ', $parts[0]));

  // Container for ajax messages.
  $result = '<div id="tmgmt-status-messages-' . strtolower($element['#parent_label'][0]) . '"></div>';

  $table = [
    '#type' => 'table',
    '#attributes' => array(
      'id' => $element['#ajaxid'],
      'class' => array($element['#zebra']),
    ),
    '#header' => [
      [
        'data' => $header_title,
        'colspan' => 4,
      ]
    ],
    '#rows' => array(
      array(
        'data' => array(
          \Drupal::service('renderer')->render($element['status']),
          \Drupal::service('renderer')->render($element['source']),
          \Drupal::service('renderer')->render($element['translation']),
          \Drupal::service('renderer')->render($element['actions']),
        ),
      ),
    ),
  ];
  $result .= \Drupal::service('renderer')->render($table);

  return $result;
}

/**
 * Renders a data item status as an HTML/CSS icon.
 */
function theme_tmgmt_local_translation_form_element_status($variables) {
  $classes = array();
  $classes[] = 'tmgmt-ui-icon';
  $classes[] = 'tmgmt-ui-icon-32'; // Icon size 32px square.
  switch ($variables['status']['#value']) {
    case TMGMT_DATA_ITEM_STATE_COMPLETED:
      $title = t('Accepted');
      $icon = 'core/misc/icons/73b355/check.svg';
      break;
    case TMGMT_DATA_ITEM_STATE_TRANSLATED:
      $title = t('Translated');
      $icon = drupal_get_path('module', 'tmgmt') . '/icons/gray-check.svg';
      break;
    case TMGMT_DATA_ITEM_STATE_UNTRANSLATED:
    default:
      $title = t('Untranslated');
      $icon = drupal_get_path('module', 'tmgmt') . '/icons/ready.svg';
      break;
  }
  return new FormattableMarkup('<div class="@c"><img src="@s" alt="@t"/><span></span></div>', array("@c" => implode(' ', $classes), "@s" => file_create_url($icon), "@t" => $title));
}
