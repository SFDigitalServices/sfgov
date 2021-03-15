<?php

/**
 * Implements hook_preprocess_toc_tree().
 */
function sfgovpl_preprocess_toc_tree(&$variables) {
  $variables['has_more_sections'] = FALSE;

  /** @var \Drupal\toc_api\TocInterface $toc */
  $toc = $variables['toc'];
  $tree = $toc->getTree();
  if (!isset($tree['below'])) {
    return;
  }

  // Show the see all sections if we have indented children.
  foreach ($tree['below'] as $key => $branch) {
    if (count($branch['children'])) {
      $variables['has_more_sections'] = TRUE;
      return;
    }
  }

}
