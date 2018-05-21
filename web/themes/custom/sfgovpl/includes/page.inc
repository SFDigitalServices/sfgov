<?php

/**
 * @file
 * Theme and preprocess functions for page.
 */

/**
 * Implements HOOK_preprocess_page().
 */
/*
function sfgovpl_preprocess_page(&$variables) {
  // Define a page template accessible logo path variable
  $variables['logopath'] = '/' . drupal_get_path('theme','sfgovpl') . '/logo.svg';
}
*/

/**
 * Implements HOOK_theme_suggestions_page_alter().
 */
/*
function sfgovpl_theme_suggestions_page_alter
(array &$suggestions, array $variables) {
  // Add custom template suggestion for specific nodes pages
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    $content_type = $node->bundle();
    $suggestions[] = 'page__'.$content_type;
  }
}
*/
