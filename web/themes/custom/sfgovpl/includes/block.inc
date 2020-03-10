<?php

use Drupal\Component\Utility\Html;

function sfgovpl_theme_suggestions_block_alter(array &$suggestions, array $variables) {
  if (isset($variables['elements']['#id']) && $variables['elements']['#id'] === 'sfgovpl_local_tasks') {
    $clean_path = Html::cleanCssIdentifier(\Drupal::service('path.current')->getPath());
    $clean_path = str_replace("-", "_", $clean_path);
    $suggestions []= 'block__sfgovpl_local_tasks_' . $clean_path;
  }
}

function sfgovpl_preprocess_block__system_branding_block(&$variables) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof \Drupal\node\NodeInterface
    && $node->bundle() == 'campaign') {
    if (!$node->get('field_logo')->isEmpty()) {
      $variables['campaign_logo'] = $node->get('field_logo')->entity;
    }
  }
}
