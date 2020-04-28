<?php

namespace Drupal\tmgmt_content\Controller;

use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Overridden class for entity translation controllers.
 */
class ContentTranslationControllerOverride extends ContentTranslationController  {

  /**
   * {@inheritdoc}
   */
  public function overview(RouteMatchInterface $route_match, $entity_type_id = NULL) {
    $build = parent::overview($route_match, $entity_type_id);
    if (\Drupal::entityTypeManager()->getAccessControlHandler('tmgmt_job')->createAccess()) {
      $build = \Drupal::formBuilder()->getForm('Drupal\tmgmt_content\Form\ContentTranslateForm', $build);
    }
    return $build;
  }

}
