<?php

namespace Drupal\sfgov_utilities\Migration\FieldDeptMigration;

use Drupal\node\Entity\Node;

class FieldDeptMigration {

  private $report;

  public function __construct() {
    $this->report = [];    
  }

  private function getNodes(string $type) {
    $nids = \Drupal::entityQuery('node')
    ->condition('type', $type)
    ->execute();
    return $nids;
  }

  public function getReport() {
    return $this->report;
  }

  public function migrateToFieldDept() {
    $nids = array_merge(
      $this->getNodes('information_page'), 
      $this->getNodes('data_story'),
      $this->getNodes('location'),
      $this->getNodes('topic'),
    );
    $nodes = Node::loadMultiple($nids);

    foreach($nodes as $node) {
      $nid = $node->id();
      $contentType = $node->getType();
      $currentFieldName = '';
      
      switch($contentType) {
        case 'information_page':
          $currentFieldName = 'field_public_body';
          break;
        
        case 'data_story':
        case 'location':
        case 'topic':
          $currentFieldName = 'field_departments';
          break;
        
        default:
      }

      $currentField = $node->get($currentFieldName);
      $currentFieldValues = $currentField->getValue();
      $fieldDept = $node->get('field_dept');
      
      if(!empty($currentFieldValues)) {
        foreach($currentFieldValues as $currentFieldValue) {
          $refId = $currentFieldValue['target_id'];
          $refNode = Node::load($refId);
  
          if(!empty($refNode)) {

            // report
            $this->report[] = [
              'nid' => $nid,
              'content_type' => $node->getType(),
              'node_title' => $node->getTitle(),
              'url' => 'https://sf.gov/node/' . $nid,
              'ref_node_title' => $refNode->getTitle(),
              'ref_id' => $refNode->id(),
            ];
  
            // assign to new field
            $fieldDept[] = [
              'target_id' => $refId
            ];
  
            // remove old ref
            $currentField->removeItem(0);
          }
        }
        $node->save();
      }
    }
  }
}
