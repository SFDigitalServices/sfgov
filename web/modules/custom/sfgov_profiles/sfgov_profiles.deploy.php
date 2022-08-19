<?php

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

function sfgov_profiles_deploy_00_resave_public_bodies() {
  $nids = \Drupal::entityQuery('node')->condition('type','public_body')->execute();
  $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

  foreach ($nodes as $node) {
    $profileGroups = $node->get('field_board_members')->getValue();
    foreach ($profileGroups as $pg) {
      $profileGroup = Paragraph::load($pg['target_id']);
      $profileGroup->save();
    }
    $node->save();
  }
}