<?php

use Drupal\views\Views;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;

/**
 * Implements hook_preprocess_views_view()
 * 
 * Adds a twig template variable for past events
 */
function sfgovpl_preprocess_views_view__meetings__page(&$variables) {
  $nid = \Drupal::routeMatch()->getParameter('arg_0');
  $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
  $variables['public_body'] = $node->toLink()->toString()->getGeneratedLink();
}

/**
 * Implements hook_preprocess_views_view()
 *
 * Adds a twig template variable for department news.
 */
function sfgovpl_preprocess_views_view__news__page_1(&$variables) {
  // Retrieve dept id.
  $nid = $variables['view']->args[0] ?? NULL;
  if ($nid) {
    $node = Node::load($nid);
    if ($node) {
      $variables['field_dept'] = $node->getTitle();
      $variables['banner_news'] = 'department';
    }
  }
}

/**
 * Implements hook_preprocess_views_view()
 *
 * Adds a twig template variable for topic news.
 */
function sfgovpl_preprocess_views_view__news__page_2(&$variables) {
  // Retrieve topic id.
  $nid = $variables['view']->args[0] ?? NULL;
  if ($nid) {
    $node = Node::load($nid);
    if ($node) {
      $variables['field_topic'] = $node->getTitle();
      $variables['banner_news'] = 'topic';
    }
  }
}

/**
 * Implements hook_preprocess_views_view()
 *
 * Adds a twig template variable for upcoming events
 */
function sfgovpl_preprocess_views_view__events__page_1(&$variables) {
  $variables['upcoming_event_count'] = getUpcomingEventCount($variables['view']);
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
