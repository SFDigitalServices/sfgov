<?php
/**
 * @param array $suggestions
 * @param array $variables
 *
 * @return array
 */
function sfgovpl_theme_suggestions_page_alter(array &$suggestions, array $variables){
  $http_error_suggestions = [
    'system.404' => 'page__404',
  ];

  $route_name = \Drupal::routeMatch()->getRouteName();
  if (isset($http_error_suggestions[$route_name])) {
    $suggestions[] = $http_error_suggestions[$route_name];
  }

  // Add content type suggestions.
  if ($node = \Drupal::request()->attributes->get('node')) {
    array_splice($suggestions, 1, 0, 'page__node__' . $node->getType());
  }

  return $suggestions;
}
