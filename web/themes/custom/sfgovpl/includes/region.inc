<?php

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function sfgovpl_theme_suggestions_region_alter(array &$suggestions, array $variables) {
  if ($variables['elements']['#region'] === 'content') {
    $path = \Drupal::service('path.current')->getPath();
    if (\Drupal::currentUser()->isAnonymous()) {
      // Anonymous user.
      switch ($path) {
        case '/user/sfgov-login':
          $suggestions[] = 'region__content__user_login';
          break;

        case '/user/sfgov-password':
          $suggestions[] = 'region__content__user_password';
          break;
      }
    }
  }
}
