<?php

/**
 * @file
 * Contains \Drupal\mandrill_activity\Routing\MandrillActivityRoutes.
 */

namespace Drupal\mandrill_activity\Routing;

use Drupal\mandrill_activity\Entity\MandrillActivity;
use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes for Mandrill Activity entities.
 *
 * This allows Mandrill activity to be displayed on any entity.
 */
class MandrillActivityRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = array();

    $activity_ids = \Drupal::entityQuery('mandrill_activity')
      ->execute();

    $activity_entities = MandrillActivity::loadMultiple($activity_ids);

    /* @var $activity \Drupal\mandrill_activity\Entity\MandrillActivity */
    foreach ($activity_entities as $activity) {
      if (!$activity->enabled) {
        continue;
      }

      $routes['entity.' . $activity->entity_type .'.mandrill_activity'] = new Route(
        // Route path.
        $activity->entity_type . '/{' . $activity->entity_type . '}/mandrill_activity',
        // Route defaults.
        array(
          '_controller' => '\Drupal\mandrill_activity\Controller\MandrillActivityController::overview',
          '_title' => 'Mandrill Activity',
        ),
        // Route requirements.
        array(
          '_permission'  => 'access mandrill activity',
        )
      );
    }

    return $routes;
  }

}
