<?php

namespace Drupal\sfgov_utilities\Migration\FieldDepartmentMigration;

use Drupal\node\Entity\Node;
use Drupal\sfgov_utilities\Utility;

class FieldDepartmentMigration {

  private $report;

  public function __construct() {
    $this->report = [];    
  }
  
  public function getReport() {
    return $this->report;
  }

  public function migrateToFieldDepartments($nodes, $fromFieldName) {
    foreach($nodes as $node) {
      $nid = $node->id();
      $contentType = $node->getType();

      $currentField = $node->get($fromFieldName);
      $currentFieldValues = $currentField->getValue();
      $fieldDepartments = $node->get('field_departments');
      
      if(!empty($currentFieldValues)) {
        foreach($currentFieldValues as $currentFieldValue) {
          $refId = $currentFieldValue['target_id'];
          $refNode = Node::load($refId);
  
          if(!empty($refNode)) {
            $this->report[] = [
              'nid' => $nid,
              'content_type' => $node->getType(),
              'language' => $node->get('langcode')->value,
              'node_title' => $node->getTitle(),
              'url' => 'https://sf.gov/node/' . $nid,
              'ref_node_title' => $refNode->getTitle(),
              'ref_id' => $refNode->id(),
            ];
  
            // assign to new field
            $fieldDepartments[] = [
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
