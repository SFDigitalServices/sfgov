<?php

use Drupal\Component\Utility\Html;

function sfgovpl_theme_suggestions_block_alter(array &$suggestions, array $variables) {
  if ($variables['elements']['#id'] === 'sfgovpl_local_tasks') {
    $clean_path = Html::cleanCssIdentifier(\Drupal::service('path.current')->getPath());
    $clean_path = str_replace("-", "_", $clean_path);
    $suggestions []= 'block__sfgovpl_local_tasks_' . $clean_path;
  }
}
