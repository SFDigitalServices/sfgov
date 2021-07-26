<?php

declare(strict_types = 1);

namespace Drupal\sfgov_event_subscriber\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class CloneEditorAccessCheck.
 */
class CloneEditorAccessCheck implements AccessInterface {

  /**
   * The user account being checked.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $account;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $currentRoute;

  /**
   * CloneEditorAccessCheck constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account interface service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   */
  public function __construct(AccountInterface $account, CurrentRouteMatch $current_route_match) {
    $this->account = $account;
    $this->currentRoute = $current_route_match;
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for the logged in user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result - forbidden, allowed, or neutral.
   */
  public function access(AccountInterface $account): AccessResult {
    // Retrieve the node and related node content.
    $node = $this->currentRoute->getParameter('node');
    if ($node && $node instanceof EntityInterface) {
      $node_type = $node->getType();
      $nid = $node->id();
      $user_id = $account->id();
      $user_roles = $account->getRoles();
      // Check editing rights for the Publication Page content type.
      // if ($node_type === 'publication_page') {
      //   $publication_reference = $node->get('field_publication_reference')->target_id;
      //   // Retrieve the current user.
      //   $user_id = $account->id();
      //   $user_roles = $account->getRoles();
      //   // Only some editors have been explicitly granted editing rights.
      //   if (in_array('editor', $user_roles) && !$this->checkEditAccess($user_id, $publication_reference)) {
      //     // The default permission is allowed so check for not allowed.
      //     return AccessResult::forbidden()->cachePerUser();
      //   }
      //   // Only some artists have been explicitly granted editing rights.
      //   if (in_array('artist', $user_roles) && $this->checkEditAccess($user_id, $nid)) {
      //     // The default permission is forbidden so check for allowed.
      //     return AccessResult::allowed()->cachePerUser();
      //   }
      // }
      // // Check editing rights for the Publication content type.
      // if ($node_type === 'publication') {
      //   // Only some editors have been explicitly granted editing rights.
      //   if (in_array('editor', $user_roles) && !$this->checkEditAccess($user_id, $nid)) {
      //     // The default permission is allowed so check for not allowed.
      //     return AccessResult::forbidden()->cachePerUser();
      //   }
      // }
      // All other node types should default to usual permissions.
      return AccessResult::forbidden();
      //return AccessResult::allowedIfHasPermission($account, 'edit any ' . $node_type . ' content')->cachePerUser();
    }
    return AccessResult::forbidden();
    //return AccessResult::neutral()->cachePerUser();
  }

  /**
   * Check if the specific editor has edit grants for the node.
   *
   * @param string $user_id
   *   The user id of the editor or artist to check.
   * @param string $publication
   *   The nid of the Publication node linked to the Publication Page to check.
   *
   * @return bool
   *   Return TRUE if the editor is allowed to edit this node.
   */
  private function checkEditAccess(string $user_id, string $publication): bool {
    // Note: for artist, the publication variable is the publication page nid.
    $allowed = TRUE;
   // $allowed = $this->permissionsManager->checkAccess($user_id, $publication);
    if (!$allowed) {
      return FALSE;
    }
    return TRUE;
  }

}
