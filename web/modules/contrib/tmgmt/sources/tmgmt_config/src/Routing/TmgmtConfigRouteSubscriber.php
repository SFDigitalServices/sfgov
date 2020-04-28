<?php

namespace Drupal\tmgmt_config\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber to alter entity translation routes.
 */
class TmgmtConfigRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Look for routes that use  ContentTranslationController and change it
    // to our subclass.
    foreach ($collection as $route) {
      if ($route->getDefault('_controller') == '\Drupal\config_translation\Controller\ConfigTranslationController::itemPage') {
        $route->setDefault('_controller', '\Drupal\tmgmt_config\Controller\ConfigTranslationControllerOverride::itemPage');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // \Drupal\config_translation\Routing\RouteSubscriber is -110,
    // make sure we are later.
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -111);
    return $events;
  }

}
