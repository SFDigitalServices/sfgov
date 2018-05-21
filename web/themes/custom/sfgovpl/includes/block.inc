<?php

/**
 * @file
 * Theme and preprocess functions for block.
 */

/**
 * Implements hook_theme_suggestions_block_alter().
 */

//function sfgovpl_theme_suggestions_block_alter(array &$suggestions, array $variables) {
//  // Set bare blocks template
//  $plugin_id = $variables['elements']['#base_plugin_id'];
//  $bare_blocks = array(
//                      "system_main_block",
//                      );
//  if (in_array($plugin_id, $bare_blocks)) {
//    $suggestions[] = 'block__bare';
//  }
//}

/**
 * Implements hook_preprocess_block().
 */
/*
function sfgovpl_preprocess_block(&$variables) {
  // Add class for a specific block
  $block_id = $variables['elements']['#id'];
  switch ($block_id) {
    case '':
      $variables['attributes']['class'][] = '';
      break;
   default:
      break;
  }

  // Block class for custom block bundles.
  // @see https://www.drupal.org/node/2724333
  if (isset($variables['elements']['content']['#block_content'])) {
    $variables['attributes']['class'][] = 'block--'. $variables['elements']['content']['#block_content']->bundle();
  }
}
*/
