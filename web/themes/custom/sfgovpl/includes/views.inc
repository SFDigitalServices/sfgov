<?php

use Drupal\views\Views;
/**
 * Implements hook_preprocess_views_view()
 * 
 * Adds a twig template variable for upcoming events
 */
function sfgovpl_preprocess_views_view__events__page_1(&$variables) {
  $variables['upcoming_event_count'] = getUpcomingEventCount($variables['view']);
}

/**
 * Implements hook_preprocess_views().
 *
 * Workaround to display the Views title.
 * See https://www.drupal.org/project/drupal/issues/2887071#comment-13554107
 */
function sfgovpl_preprocess_views_view__meetings__page(&$variables) {
  $view = $variables['view'];
  $view_id = $variables['id'];
  $view_display_id = $variables['display_id'];

  $title = $view->storage->getDisplay($view_display_id)['display_options']['title'];
  $variables['title'] = $title;
}

/**
 * Implements hook_preprocess_views_view()
 * 
 * Adds a twig template variable for past events
 */
function sfgovpl_preprocess_views_view__events__page_2(&$variables) {
  $variables['upcoming_event_count'] = getUpcomingEventCount($variables['view']);
}

function getUpcomingEventCount($view) {
  if(!$view) {
    return 0;
  } else {
    $deptId = !empty($view->args[0]) ? $view->args[0]: null;
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
