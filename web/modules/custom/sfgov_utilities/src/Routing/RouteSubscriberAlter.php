<?php

namespace Drupal\sfgov_utilities\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter dynamic route events.
 */
class RouteSubscriberAlter extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Allow the moderation module to modify access for the node edit form.
    if ($route = $collection->get('entity.node.edit_form')) {
      $route->setRequirement('_custom_access', 'sfgov_moderation.access_checker::access');
    }
  }

}
