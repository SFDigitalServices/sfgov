<?php

namespace Drupal\sfgov_api;

use Drupal\paragraphs\Entity\Paragraph as EntityParagraph;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgovApiParagraphPluginBase extends SfgovApiPluginBase {

  protected $entity_type = 'paragraph';

  public function setBaseData($entity) {
    $base_data = [
      'drupal_id' => $entity->id(),
    ];
    return $base_data;
  }

  public function getEntities($entity_type, $bundle, $langcode = 'en', $entity_id = NULL) {
    if ($entity_id) {
      $entities = EntityParagraph::load($entity_id) ? [EntityParagraph::load($entity_id)] : [];
    }
    else {
      $pids = \Drupal::entityQuery($entity_type)->condition('type', $bundle)->execute();
      $entities = EntityParagraph::loadMultiple($pids);
    }

    if ($langcode != 'en') {
      foreach ($entities as $key => $entity) {
        if ($entity->hasTranslation($langcode)) {
          $entities[$key] = $entity->getTranslation($langcode);
        }
        else {
          unset($entities[$key]);}
      }
    }

    return $entities;
  }

}
