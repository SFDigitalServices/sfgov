<?php

declare(strict_types = 1);

namespace Drupal\sfgov_event_subscriber\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class CloneRouteAlterSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $var = 'thiss';
    if ($route = $collection->get('quick_node_clone.node.quick_clone')) {
      $route->setRequirement('_clone_editor_access_check', 'TRUE');
    }
  }

}
