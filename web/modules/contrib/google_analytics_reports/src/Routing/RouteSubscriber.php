<?php

namespace Drupal\google_analytics_reports\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
/**
 * Subscriber for google analytics reports routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('google_analytics_reports_api.settings')) {
      $route->setDefault('_form', 'Drupal\google_analytics_reports\Form\GoogleAnalyticsReportsAdminSettingsForm');
    }
  }

}
