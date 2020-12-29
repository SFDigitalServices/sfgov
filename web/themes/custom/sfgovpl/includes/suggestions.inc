<?php

/**
 * @file
 * Alter hook implementations for template suggestions.
 */

use Drupal\node\NodeInterface;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function sfgovpl_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  $http_error_suggestions = [
    'system.404' => 'page__404',
  ];

  // @todo I believe we can remove this, as suggestions for this have since been
  // added to Drupal core.
  $route_name = \Drupal::routeMatch()->getRouteName();
  if (isset($http_error_suggestions[$route_name])) {
    $suggestions[] = $http_error_suggestions[$route_name];
  }

  // Make sure we have a node object that works on revisions, diffs, etc.
  _sfgovpl_node_object($variables);

  if (!empty($variables['node'])) {
    if ($variables['node'] instanceof NodeInterface) {
      $node = $variables['node'];
    }
    elseif (!empty($variables['node_revision'])) {
      $node = $variables['node_revision'];
    }

    if (!empty($node)) {
      // Add content type suggestions.
      array_splice($suggestions, 1, 0, 'page__node__' . $node->bundle());
    }
  }

  return $suggestions;
}
