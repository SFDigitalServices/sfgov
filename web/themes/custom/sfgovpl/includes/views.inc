<?php

use Drupal\views\Views;

// $module_handler = Drupal::service('module_handler');
// $devel_path = $module_handler->getModule('devel')->getPath();
// require_once($devel_path.'/kint/kint/Kint.class.php');
// Kint::$maxLevels = 4;

function sfgovpl_preprocess_views_view__events__page_1(&$variables) {
  $variables['upcoming_event_count'] = getUpcomingEventCount($variables['view']);
}

function sfgovpl_preprocess_views_view__events__page_2(&$variables) {
  $variables['upcoming_event_count'] = getUpcomingEventCount($variables['view']);
}

function getUpcomingEventCount($view) {
  if(!$view) {
    return 0;
  } else {
    $deptId = $view->args[0];
    $viewId = $view->storage->id();
    $eventsView = Views::getView($viewId);
    $eventsView->setArguments([$deptId]);
    $eventsView->setDisplay('page_1');
    $eventsView->get_total_rows = TRUE;
    $eventsView->preExecute();
    $eventsView->execute();
    return $eventsView->total_rows;
  }
}