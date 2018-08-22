<?php

  namespace Drupal\sfgov_utilities;

  class Utility {
    public static function getNodesOfContentType($contentType) {
      $storage = \Drupal::getContainer()->get('entity_type.manager')->getStorage('node');
      $nids = $storage->getQuery();

      if(!$nids) return null;

      $nids = $nids->condition('type', $contentType)
      ->condition('status', 1)
      ->sort('title')
      ->execute();
      return empty($nids) ? null : $storage->loadMultiple($nids);;
    }

    public static function createSelectOptionsOfContentTypeNodes($contentType) {
      $options = [];
      $nodes = Utility::getNodesOfContentType($contentType);
      foreach($nodes as $node) {
        $options[$node->id()] = $node->getTitle();
      }
      return $options;
    }
  }