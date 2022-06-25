<?php

use Drupal\Core\Url;
use Drupal\views\Views;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;

/**
 * Implements hook_preprocess_views_view()
 *
 * Adds a twig template variable for past events
 */
function sfgovpl_preprocess_views_view__meetings__page(&$variables) {
  $nid = Drupal::routeMatch()->getParameter('arg_0');
  $node = Drupal::entityTypeManager()->getStorage('node')->load($nid);
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
function sfgovpl_preprocess_views_view__events__block(&$variables) {
  $view = $variables['view'];
  $options = [
    'attributes' => [
      'target' => "_blank"
    ]
  ];

  // [SG-1007]
  // Generate a More link for the Department events block, with the departments
  // label processed and set as the argument.
  if (!empty($view->args[0])) {
    $node_manager = Drupal::entityTypeManager()->getStorage('node');
    $department_id = $view->args[0];
    $node = $node_manager->load($department_id);

    // Start by setting the link to go to upcoming department events.
    $view_id = 'view.events.page_4';
    $options['attributes']['title'] = t('View upcoming events for @department', ['@department' => $node->label()]);

    // Change to the upcoming topic events if the contextual node is a topic.
    if ($node->bundle() == 'topic') {
      $view_id = 'view.events.page_3';
      $options['attributes']['title'] = t('View upcoming events for the topic of @topic', ['@topic' => $node->label()]);
    }

    // Generate the link to the contextually filtered view. We have to use the
    // ID of the topic or department. The title can't handle certain characters
    // when trying to process the filter.
    $variables['more']['#url'] = Url::fromRoute($view_id, ['node' => $node->id()], $options);
  }
}

/**
 * Implements hook_preprocess_views_view()
 *
 * Adds a twig template variable for upcoming events
 */
function sfgovpl_preprocess_views_view__events__page(&$variables) {
  $view = $variables['view'];
  $variables['upcoming_event_count'] = get_upcoming_event_count($view);
  $variables['upcoming_events_url'] = get_upcoming_event_view_url($view);
  $variables['past_events_url'] = get_past_event_view_url($view);
  $variables['is_upcoming_event_display'] = get_is_upcoming_event_display($view);
}

/**
 * Implements hook_preprocess_views_view()
 *
 * Adds a twig template variable for upcoming events
 */
function sfgovpl_preprocess_views_view__events__page_1(&$variables) {
  $view = $variables['view'];
  $variables['upcoming_event_count'] = get_upcoming_event_count($view);
  $variables['upcoming_events_url'] = get_upcoming_event_view_url($view);
  $variables['past_events_url'] = get_past_event_view_url($view);
  $variables['is_upcoming_event_display'] = get_is_upcoming_event_display($view);
}

/**
 * Implements hook_preprocess_views_view()
 *
 * Adds a twig template variable for past events
 */
function sfgovpl_preprocess_views_view__events__page_2(&$variables) {
  $view = $variables['view'];
  $variables['upcoming_event_count'] = get_upcoming_event_count($view);
  $variables['upcoming_events_url'] = get_upcoming_event_view_url($view);
  $variables['past_events_url'] = get_past_event_view_url($view);
  $variables['is_upcoming_event_display'] = get_is_upcoming_event_display($view);
}

/**
 * Get a count of upcoming events.
 *
 * Can get a count of all events, or a count filtered by a topic or department.
 *
 * @param $view
 *
 * @return int|null
 */
function get_upcoming_event_count($view): ?int {
  if(!$view) {
    return 0;
  }

  $display = $view->current_display;
  switch ($display) {
    // Set the upcoming events link for a topic filtered display.
    case 'page_3': // Event topic display
    case 'page_5': // Past topic display
      $display_to_count = 'page_3';
      break;

    // Set the upcoming events link for a department filtered display.
    case 'page_4': // Event department display
    case 'page_6': // Past department display
      $display_to_count = 'page_4';
      break;

    // Set the upcoming events link for the all events display.
    case 'page_1': // Event all display
    case 'page_2': // Past all display
    default:
      $display_to_count = 'page_1';
      break;
  }

  $dept_id = !empty($view->args[0]) ? $view->args[0]: null;
  $viewId = $view->storage->id();
  $eventsView = Views::getView($viewId);
  $eventsView->setArguments([$dept_id]);
  $eventsView->setDisplay($display_to_count);
  $eventsView->get_total_rows = TRUE;
  $eventsView->preExecute();
  $eventsView->execute();

  return $eventsView->total_rows;
}

/**
 * Get the relevant upcoming event url.
 *
 * Can return the all events url, or the url filtered by topic or department.
 */
function get_upcoming_event_view_url($view) {
  $display = $view->current_display;
  $parameters = $view->getUrl()->getRouteParameters();

  switch ($display) {
    // Set the upcoming events link for a topic filtered display.
    case 'page_3': // Event topic display
    case 'page_5': // Past topic display
      return Url::fromRoute('view.events.page_3', $parameters)->toString();

    // Set the upcoming events link for a department filtered display.
    case 'page_4': // Event department display
    case 'page_6': // Past department display
      return Url::fromRoute('view.events.page_4', $parameters)->toString();

    // Set the upcoming events link for the all events display.
    case 'page_1': // Event all display
    case 'page_2': // Past all display
    default:
      return '/events';
  }
}

/**
 * Get the relevant past event url.
 *
 * Can return all past events url, or the url filtered by topic or department.
 */
function get_past_event_view_url($view) {
  $display = $view->current_display;
  $parameters = $view->getUrl()->getRouteParameters();

  switch ($display) {
    // Set the past events link for a topic filtered display.
    case 'page_3': // Event topic display
    case 'page_5': // Past topic display
      return Url::fromRoute('view.events.page_5', $parameters)->toString();

    // Set the past events link for a department filtered display.
    case 'page_4': // Event department display
    case 'page_6': // Past department display
      return Url::fromRoute('view.events.page_6', $parameters)->toString();

    // Set the past events link for the all events display.
    case 'page_1': // Event all display
    case 'page_2': // Past all display
    default:
      return '/past-events';
  }
}

/**
 * Determine if the display is an upcoming event type of display or not.
 *
 * @param $view
 *
 * @return bool
 */
function get_is_upcoming_event_display($view): bool {
  $display = $view->current_display;

  switch ($display) {
    // Set the past events link for a topic filtered display.
    case 'page_1': // Event all display
    case 'page_3': // Event topic display
    case 'page_4': // Past topic display
      return TRUE;
    default:
      return FALSE;
  }
}
