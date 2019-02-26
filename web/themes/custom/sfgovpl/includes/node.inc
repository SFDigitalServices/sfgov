<?php

function sfgovpl_preprocess_node(&$variables) {
  if(!empty($variables['node'])) {
    $content_type = $variables['node']->getType();
    $tags = [];
    if($content_type == 'information_page') {
      $tags = info_page_tags();
    }
    $variables['sfgov_tags'] = $tags;
  }
}

// expose the information page "part of" tags for template
function info_page_tags() {
  $node = \Drupal::request()->attributes->get('node');
  $tx_tags_exposed = [];
  if($node && $node->hasField('field_transactions')) {
    $tx_nodes = $node->get('field_transactions')->referencedEntities();
    foreach($tx_nodes as $tx_node) {
      $tx_tags_exposed[] = [
        'name' => $tx_node->getTitle(),
        'url' => $tx_node->toUrl()->toString(),
      ];
    }
  }
  return $tx_tags_exposed;
}