<?php

/**
 * @file
 * Views hooks.
 */

use Drupal\date_popup\DatePopup;
use Drupal\date_popup\DatePopupSearchApi;
use Drupal\date_popup\DatetimePopup;

/**
 * Implements hook_views_plugins_filter_alter().
 */
function date_popup_views_plugins_filter_alter(&$info) {
  $info['date']['class'] = DatePopup::class;
  if (isset($info['datetime'])) {
    $info['datetime']['class'] = DatetimePopup::class;
  }
  if (isset($info['search_api_date'])) {
    $info['search_api_date']['class'] = DatePopupSearchApi::class;
  }
}
