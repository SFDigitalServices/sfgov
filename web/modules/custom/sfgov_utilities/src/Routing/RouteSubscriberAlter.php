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

    // @todo - Do we need to include delete page as well?
    if ($route = $collection->get('entity.node.edit_form')) {
      // Allow the moderation module to modify access for the node edit form.
      $route->setRequirement('_custom_access', 'Drupal\sfgov_moderation\Access\ModerationAccessCheck::access');
    }
  }

}
