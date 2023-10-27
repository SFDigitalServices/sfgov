<?php

  namespace Drupal\sfgov_utilities;

  use Drupal\node\Entity\Node;

  class Utility {
    public static function getNodesOfContentType($contentType) {
      $storage = \Drupal::getContainer()->get('entity_type.manager')->getStorage('node');
      $nids = $storage->getQuery();

      if(!$nids) return null;

      $nids = $nids->condition('type', $contentType)
      ->condition('status', 1)
      ->sort('title')
      ->accessCheck()
      ->execute();
      return empty($nids) ? null : $storage->loadMultiple($nids);;
    }

    /*
    * get nodes and their translations for a given content type machine name string regardless of published status
    */
    public static function getNodes($contentType) {
      $nids = \Drupal::entityQuery('node')
      ->condition('type', $contentType)
      ->accessCheck()
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
  }