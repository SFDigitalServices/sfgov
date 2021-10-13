<?php

namespace Drupal\sfgov_user\Routing;

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
    // Change path '/user/login' to '/user/sfgov-login'.
    if ($route = $collection->get('user.login')) {
      $route->setPath('/user/sfgov-login');
    }
    // Change path '/user/password' to '/user/sfgov-password'.
    if ($route = $collection->get('user.pass')) {
      $route->setPath('/user/sfgov-password');
    }

  }

}
