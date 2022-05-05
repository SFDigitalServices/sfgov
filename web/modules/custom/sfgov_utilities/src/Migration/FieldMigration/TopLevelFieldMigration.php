<?php

namespace Drupal\sfgov_utilities\Migration\FieldMigration;

use Drupal\node\Entity\Node;
use Drupal\sfgov_utilities\Utility;

class TopLevelFieldMigration {

  private $report;

  public function __construct() {
    $this->report = [];    
  }
  
  public function getReport() {
    return $this->report;
  }

  /*
  * migrates values from one field to another field on the same content type
  */
  public function migrate($nodes, $fromFieldName, $toFieldName) {
    foreach($nodes as $node) {
      if(!$node->hasField($fromFieldName)) {
        throw new \Exception("Field `$fromFieldName` does not exist on node of type `" . $node->getType() . "` with id " . $node->id());
      }

      $nid = $node->id();
      $contentType = $node->getType();

      $fromField = $node->get($fromFieldName);
      $fromFieldValues = $fromField->getValue();
      $toField = $node->get($toFieldName);
      
      if(!empty($fromFieldValues)) {
        foreach($fromFieldValues as $fromFieldValue) {
          $refId = $fromFieldValue['target_id'];
          $refNode = Node::load($refId);
          $refNodeTitle = $refNode ? $refNode->getTitle() : 'empty reference, no title';
          
          $reportLang = $node->get('langcode')->value != 'en' ? ($node->get('langcode')->value . '/') : '';
          $this->report[] = [
            'nid' => $nid,
            'content_type' => $node->getType(),
            'language' => $node->get('langcode')->value,
            'node_title' => $node->getTitle(),
            'url' => 'https://sf.gov/'. $reportLang . 'node/' . $nid,
            'status' => $node->isPublished(),
            'field_from' => $fromFieldName,
            'field_to' => $toFieldName,
            'ref_node_title' => $refNodeTitle,
            'ref_id' => $refId,
          ];

          // assign to new field
          $toField[] = [
            'target_id' => $refId
          ];

          // remove old ref
          $fromField->removeItem(0);
        }
        $node->save();
      }
    }
  }
}
