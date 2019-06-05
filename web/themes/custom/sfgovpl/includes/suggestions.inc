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

  return $suggestions;
}
