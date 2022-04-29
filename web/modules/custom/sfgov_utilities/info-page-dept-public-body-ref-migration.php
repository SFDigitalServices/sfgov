<?php

use Drupal\node\Entity\Node;

$nids = \Drupal::entityQuery('node')->condition('type','information_page')->execute();
$nodes = Node::loadMultiple($nids);

$report = [];

foreach($nodes as $node) {
  $nid = $node->id();
  if($nid==4220) {
    $fieldPublicBody = $node->get('field_public_body');
    $fieldPublicBodyValues = $fieldPublicBody->getValue();
    $fieldPublicBodyCount = count($fieldPublicBodyValues);
    if($fieldPublicBodyCount > 0) {
      $fieldDept = $node->get('field_dept');
      for($i=0; $i<$fieldPublicBodyCount; $i++) {
        $refId = $fieldPublicBodyValues[$i]['target_id'];
        $oldRefNode = Node::load($refId);
  
        if(!empty($oldRefNode)) { // some older nodes may have empty references, ignore them
          $report[] = [
            'nid' => $nid,
            'node_title' => $node->getTitle(),
            'url' => 'https://sf.gov/node/' . $nid,
            'field_public_body_ref' => $oldRefNode->getTitle(),
            'field_public_body_ref_id' => $oldRefNode->id(),
          ];
    
          // assign to new field
          $fieldDept[] = [
            'target_id' => $refId
          ];
        }
  
        // remove old ref
        $fieldPublicBody->removeItem(0);
      }
    }
    $node->save();
  }
}
