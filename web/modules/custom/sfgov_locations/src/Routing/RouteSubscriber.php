<?php

namespace Drupal\sfgov_locations\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Use the admin theme for the ECK entity add form.
    if ($route = $collection->get('eck.entity.add')) {
      $route->setOption('_admin_route', 'TRUE');
    }
  }

}
