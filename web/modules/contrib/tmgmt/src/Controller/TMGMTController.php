<?php

namespace Drupal\tmgmt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for tmgmt routes.
 */
class TMGMTController extends ControllerBase {

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * Constructs a new TMGMTLocalController.
   *
   * @param \Drupal\system\SystemManager $system_manager
   *   System manager service.
   */
  public function __construct(SystemManager $system_manager) {
    $this->systemManager = $system_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('system.manager'));
  }

  /**
   * Provides a single block from the administration menu as a page.
   */
  public function translatorAdminMenuBlockPage() {
    $contents = $this->systemManager->getBlockContents();
    if (count($contents['#content']) === 1) {
      /** @var \Drupal\Core\Url $url */
      $url = reset($contents['#content'])['url'];
      return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
    }
    return $contents;
  }

}
