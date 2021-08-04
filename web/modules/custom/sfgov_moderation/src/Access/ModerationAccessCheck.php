<?php

namespace Drupal\sfgov_moderation\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Custom access checking for SF.gov.
 */
class ModerationAccessCheck implements AccessInterface {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructor.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {

    // Get values. Set defaults.
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $node = $route_match->getParameter('node');
    $bundle = $node->bundle();
    $access = AccessResult::allowedIfHasPermission($account, sprintf('edit any %s content', $bundle));

    // Deny access to users that don't have permission
    // to edit a node's translation.
    if ($language != 'en') {
      $permission = sprintf('translate %s node', $bundle);
      $access = $account->hasPermission($permission) ? AccessResult::allowed() : AccessResult::forbidden();

    }
    return $access;

  }

}
