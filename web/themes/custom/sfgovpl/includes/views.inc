<?php

use Drupal\Core\Url;
use Drupal\views\Views;
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
 *
 * @param $variables
 * @param $display_id
 */
function sfgovpl_preprocess_views_view__events__block(&$variables, $display_id) {
  $view = $variables['view'];
  $view_id = $view->id();
  $entity_manager = \Drupal::entityTypeManager();
  $node_manager = $entity_manager->getStorage('node');

  $options = [];

  // [SG-1007]
  // Generate a More link for the Department events block, with the departments
  // label processed and set as the argument.
  if (!empty($view->args[0])) {
    $node = $node_manager->load($view->args[0]);
    if ($node instanceof Node) {
      
      // Get some managers ready for later use.
      $view_manager = \Drupal::entityTypeManager()->getStorage('view');

      // The the title and machine/url friendly version of the title for the
      // topic or department we are going to link to.
      $title = !empty($node) ? $node->label() : 'this department';
      $url_friendly_title = \Drupal::service('pathauto.alias_cleaner')
        ->cleanString($title);

      // Start by setting the ids of the upcoming and past department events.
      $upcoming_display_id = 'page_4';
      $past_display_id = 'page_6';
      $options['attributes']['title'] = t('View upcoming events for @department', ['@department' => $title]);

      // Change to the upcoming and past topic events if the contextual node
      // is a topic.
      if ($node->bundle() == 'topic') {
        $upcoming_display_id = 'page_3';
        $past_display_id = 'page_5';
        $options['attributes']['title'] = t('View upcoming events for the topic of @topic', ['@topic' => $title]);
      }

      // Get information about the view page that we are wanting to link to.
      // This could be either upcoming or past events page displays for topics
      // or departments. We run through both upcoming and past to make sure that
      // those aliases are in place.
      foreach ([$upcoming_display_id, $past_display_id] as $display_id) {
        // Get the information of the upcoming and past displays.
        $event_view = $view_manager->load($view_id)
          ->getExecutable();
        $event_view->initDisplay();
        $event_view->setDisplay($display_id);
        $event_view->execute();

        // Generate a system url to the contextually filtered view. We have to
        // use the ID of the topic or department. The title can't handle certain
        // characters when trying to process the filter.
        $url = Url::fromRoute("view.$view_id.$display_id", ['node' => $node->id()], $options);
        // Numeric ID based path. (example: /events/topics/25)
        $internal_path = '/' . $url->getInternalPath();
        // Human friendly based path. (example: /events/topics/immigrants)
        $generated_path = '/' . str_replace('%node', $url_friendly_title, $event_view->getPath());

        // Create an alias for the filtered view page if one does not exist.
        sfgov_utilities_create_alias($internal_path, $generated_path);
      }

      // Now If we have generated an alias, rebuild the url for the link so it can
      // pick up the alias we just generated.
      $variables['more']['#url'] = Url::fromRoute("view.$view_id.$upcoming_display_id", ['node' => $node->id()], $options);
    }
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
  if (!$view) {
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

  $dept_id = !empty($view->args[0]) ? $view->args[0] : null;
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
