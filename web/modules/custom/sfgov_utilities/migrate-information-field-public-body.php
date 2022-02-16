<?php

use Drupal\node\Entity\Node;

$nids = \Drupal::entityQuery('node')->condition('type','information_page')->execute();
$nodes = Node::loadMultiple($nids);

$report = [];

foreach($nodes as $node) {
  $nid = $node->id();
  $fieldDept = $node->get('field_dept');
  $fieldDeptValues = $fieldDept->getValue();
  $fieldDeptCount = count($fieldDeptValues);
  if($fieldDeptCount > 0) {
    $fieldDeptOrPublicBody = $node->get('field_public_body');
    for($i=0; $i<$fieldDeptCount; $i++) {
      $refId = $fieldDeptValues[$i]['target_id'];
      $oldRefNode = Node::load($refId);

      if(!empty($oldRefNode)) { // some older nodes may have empty references, ignore them
        $report[] = [
          'nid' => $nid,
          'node_title' => $node->getTitle(),
          'url' => 'https://sf.gov/node/' . $nid,
          'field_dept_ref' => $oldRefNode->getTitle(),
          'field_dept_ref_id' => $oldRefNode->id(),
        ];
  
        // assign to new field
        $fieldDeptOrPublicBody[] = [
          'target_id' => $refId
        ];
      }

      // remove old ref
      $fieldDept->removeItem(0);
    }
  }
  $node->save();
}

// echo json_encode($report, JSON_UNESCAPED_SLASHES) . "\n";