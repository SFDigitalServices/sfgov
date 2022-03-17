<?php

use Drupal\node\Entity\Node;

$nids = \Drupal::entityQuery('node')->condition('type','department')->execute();
$nodes = Node::loadMultiple($nids);

$report = [];

foreach($nodes as $node) {
  $nid = $node->id();
  $nodeTitle = $node->getTitle();
  $fieldAboutDescription = $node->get('field_about_description')->getValue();
  $fieldAboutOrDescription = $node->get('field_about_or_description')->getValue();

  // field_about_or_description is preferred over field_about_description
  // process only if a dept's field_about_or_description is empty and field_about_description is not empty

  if(empty($fieldAboutOrDescription) && !empty($fieldAboutDescription)) {
    // print_r($fieldAboutDescription);
    echo "($nid) $nodeTitle\n";
    echo "\t" . $fieldAboutDescription[0]['value'];
    echo "\n";
  }
}
