<?php

/**
 * @file
 * Preprocess functions for the 'page' theme hooks.
 */

use Drupal\node\NodeInterface;

/**
 * Implements theme_preprocess_HOOK().
 */
function sfgovpl_preprocess_page__node__campaign(&$variables) {
  // Make sure we have a node object that works on revisions, diffs, etc.
  _sfgovpl_node_object($variables);

  if (!empty($variables['node']) && $variables['node'] instanceof NodeInterface) {
    $node = $variables['node'];

    // Conditionally render Logo or Title.
    if ($node->field_logo->entity) {
      $variables['campaign_branding'] = $node->field_logo->view([
        'type' => 'image',
        'label' => 'hidden',
        'settings' => [
          'image_style' => 'campaign_logo',
        ],
      ]);
    }
    else {
      $variables['campaign_branding'] = [
        '#markup' => '<h1>' . $node->label() . '</h1>',
      ];
    }
  }
}
