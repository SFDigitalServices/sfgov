<?php

/**
 * @file
 * Preprocess functions for the 'page' theme hooks.
 */

use Drupal\node\NodeInterface;

/**
 * Implements theme_preprocess_page_title(). 
 */
function sfgovpl_preprocess_page_title(&$variables) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if (!empty($node) && $node instanceof NodeInterface) {
    if ($node->bundle() == 'report') {
      // Hide page title on report nodes.
      $variables['title'] = '';
    }
  }
}

/**
 * Implements theme_preprocess_page
 * @see node.inc for _sfgovpl_negotiate_translation
 */
function sfgovpl_preprocess_page__node__form_page(&$variables) {
  _sfgovpl_negotiate_translation($variables);
}

/**
 * Implements theme_preprocess_HOOK().
 */
function sfgovpl_preprocess_page__node__campaign(&$variables) {
  // Make sure we have a node object that works on revisions, diffs, etc.
  _sfgovpl_node_object($variables);

  if (!empty($variables['node']) && $variables['node'] instanceof NodeInterface) {
    $node = $variables['node'];
    $view_mode = isset($variables['view_mode']) ? $variables['view_mode'] : NULL;
    $node_variables =  _sfgovpl_node_notranslate($node, $view_mode);

    $class = '';
    if (!empty($node_variables['notranslate'])) {
      $class = ' class="notranslate"';
    }
  }
}
