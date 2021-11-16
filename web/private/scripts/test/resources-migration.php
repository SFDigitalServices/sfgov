<?php

require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity;

$users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail'=>'webmaster@sfgov.org']);
$user = reset($users);
$user_id = $user->id();

$nids = \Drupal::entityQuery('node')->condition('type','campaign')->execute();
$nodes = Node::loadMultiple($nids);

foreach($nodes as $node) {
  $title = $node->getTitle();
  echo $node->gettype() . ": " . $title . " (". $node->id() . ")\n";
  // check for field
  if ($node->hasField('field_contents')) {
    $fieldValues = $node->get('field_contents')->getValue();
    if (!empty($fieldValues)) {
      foreach ($fieldValues as $fieldValue) {
        $targetId = $fieldValue['target_id'];
        $paragraph = Paragraph::load($targetId);
        $paragraphType = $paragraph->getType();
        if ($paragraphType == 'campaign_resources') {
          echo "  target_id:" . $targetId . ", paragraph type:" . $paragraphType . "\n";
        }
      }
    }
  }
}
