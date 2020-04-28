<?php

namespace Drupal\group_test_content_moderation\Access;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Decorate the content moderation latest revision check.
 */
class LatestRevisionCheck implements AccessInterface {

  /**
   * The content moderation latest version access service.
   *
   * @var \Drupal\content_moderation\Access\LatestRevisionCheck
   */
  protected $inner;

  /**
   * Constructs the service decorator.
   *
   * @param \Drupal\Core\Routing\Access\AccessInterface $inner
   *   The inner service.
   */
  public function __construct(AccessInterface $inner) {
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $access = $this->inner->access($route, $route_match, $account);

    if ($route->getOption('_explicit_deny')) {
      return AccessResultForbidden::forbidden('Explicit access denial');
    }

    return $access;
  }

}
