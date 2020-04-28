<?php

namespace Drupal\tmgmt_local\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new RouteSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($collection->get('tmgmt.admin_tmgmt')) {
      $route = $collection->get('tmgmt.admin_tmgmt');
      $route->addRequirements([
        '_permission' => $collection->get('tmgmt.admin_tmgmt')
          ->getRequirement('_permission') . '+administer translation tasks+provide translation services',
      ]);
    }
    foreach ($collection->all() as $route) {
      if ((strpos($route->getPath(), '/translate') === 0 && $this->configFactory->get('tmgmt_local.settings')->get('use_admin_theme'))
        || strpos($route->getPath(), '/manage-translate') === 0) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

}
