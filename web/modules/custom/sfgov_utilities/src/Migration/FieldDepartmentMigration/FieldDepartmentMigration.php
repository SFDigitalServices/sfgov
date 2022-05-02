<?php

namespace Drupal\sfgov_utilities\Migration\FieldDepartmentMigration;

use Drupal\node\Entity\Node;

class FieldDepartmentMigration {

  private $report;

  public function __construct() {
    $this->report = [];    
  }

  private function getNodes(string $type) {
    $nids = \Drupal::entityQuery('node')
    ->condition('type', $type)
    ->execute();

    $languages = ['es','fil','zh-hant'];
    
    $nodes = Node::loadMultiple($nids);
    $nodeTranslations = [];

    foreach($nodes as $node) {
      foreach($languages as $language) {
        if($node->hasTranslation($language)) {
          $nodeTranslations[] = $node->getTranslation($language);
        }
      }
    }

    return array_merge($nodes, $nodeTranslations);
  }

  public function getReport() {
    return $this->report;
  }

  public function migrateToFieldDepartments() {
    $nodes = array_merge(
      $this->getNodes('information_page'),
      $this->getNodes('campaign'),
      $this->getNodes('department_table'),
      $this->getNodes('event'),
      $this->getNodes('form_confirmation_page'),
      $this->getNodes('meeting'),
      $this->getNodes('news'),
      $this->getNodes('resource_collection')
    );

    foreach($nodes as $node) {
      $nid = $node->id();
      $contentType = $node->getType();
      $currentFieldName = '';
      
      switch($contentType) {
        case 'information_page':
          $currentFieldName = 'field_public_body';
          break;
        case 'campaign':
        case 'department_table':
        case 'event':
        case 'form_confirmation_page':
        case 'news':
        case 'resource_collection':
          $currentFieldName = 'field_dept';
          break;
        default:
      }

      $currentField = $node->get($currentFieldName);
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
