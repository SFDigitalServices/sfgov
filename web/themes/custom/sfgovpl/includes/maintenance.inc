<?php

/**
 * @file
 * Preprocessing functions related to the maintenance page.
 */

/**
 * Implements hook_preprocess_maintenance_page().
 */
function sfgovpl_preprocess_maintenance_page(&$variables) {
  $variables['maintenance_messages'] = [];

  // Get all languages.
  $languages = \Drupal::languageManager()->getLanguages();
  // Load the language_manager service.
  $language_manager = \Drupal::service('language_manager');
  // Note the original language, as we need to revert back to this.
  $original_language = $language_manager->getConfigOverrideLanguage();

  foreach ($languages as $key => $language) {
    // Override the language in order to get the message for each language.
    $language_manager->setConfigOverrideLanguage($language);
    $variables['maintenance_messages'][$key] = \Drupal::config('system.maintenance')->get('message');
  }

  // Set the configuration language back.
  $language_manager->setConfigOverrideLanguage($original_language);
}
