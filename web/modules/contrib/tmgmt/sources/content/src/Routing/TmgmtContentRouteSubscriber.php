<?php

namespace Drupal\tmgmt_content\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber to alter entity translation routes.
 */
class TmgmtContentRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Look for routes that use  ContentTranslationController and change it
    // to our subclass.
    foreach ($collection as $route) {
      if ($route->getDefault('_controller') == '\Drupal\content_translation\Controller\ContentTranslationController::overview') {
        $route->setDefault('_controller', '\Drupal\tmgmt_content\Controller\ContentTranslationControllerOverride::overview');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    //  ContentTranslationRouteSubscriber is -100, make sure we are later.
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -211);
    return $events;
  }

}
