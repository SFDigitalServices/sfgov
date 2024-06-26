<?php

use Drupal\Component\Utility\Html;

function sfgovpl_theme_suggestions_block_alter(array &$suggestions, array $variables) {
  if (isset($variables['elements']['#id']) && $variables['elements']['#id'] === 'sfgovpl_local_tasks') {
    $clean_path = Html::cleanCssIdentifier(\Drupal::service('path.current')->getPath());
    $clean_path = str_replace("-", "_", $clean_path);
    $suggestions []= 'block__sfgovpl_local_tasks_' . $clean_path;
  }

  // Block suggestions for custom block bundles.
  if (isset($variables['elements']['content']['#block_content'])) {
    array_splice($suggestions, 1, 0, 'block__type__' . $variables['elements']['content']['#block_content']->bundle());
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function sfgovpl_preprocess_gtranslate(&$variables) {
  // Language picker should always reflect each option in the language
  // it signifies.
  $replace = [
    'English' => 'English',
    'Spanish' => 'Español',
    'Filipino' => 'Filipino',
    'Chinese (Traditional)' => '繁體中文',
  ];
  $variables['gtranslate_html'] = str_replace(array_keys($replace), $replace, $variables['gtranslate_html']);
}
