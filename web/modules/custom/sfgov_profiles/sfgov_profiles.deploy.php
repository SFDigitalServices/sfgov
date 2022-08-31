<?php

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

function sfgov_profiles_deploy_00_resave_public_bodies() {
  $nids = \Drupal::entityQuery('node')->condition('type','public_body')->execute();
  $nodes = Node::loadMultiple($nids);

  foreach ($nodes as $node) {
    $profileGroups = $node->get('field_board_members')->getValue();

    foreach ($profileGroups as $pg) {
      $profileGroup = Paragraph::load($pg['target_id']);
      $profileGroup->save();
    }

    $node->save();
  }
}

function sfgov_profiles_deploy_01_migrate_bio() {
  $nids = \Drupal::entityQuery('node')->condition('type','person')->execute();
  $nodes = Node::loadMultiple($nids);

  foreach ($nodes as $node) {
    $bio = $node->get('field_biography')->value;
    $node->get('body')->value = $bio;
    $node->get('body')->format = 'sf_restricted_html';
    $node->save();
  }
}