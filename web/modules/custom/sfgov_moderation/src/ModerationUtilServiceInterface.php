<?php

namespace Drupal\sfgov_moderation;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Interface ModerationUtilServiceInterface.
 */
interface ModerationUtilServiceInterface {

  /**
   * Check if an account belongs to a department.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The given account.
   * @param \Drupal\node\NodeInterface $department
   *   The department node.
   *
   * @return bool
   * If the node belongs to a department, returns true or false. True if the
   * node has no department assigned.
   */
  public function accountBelongsToDepartment(AccountInterface $account, NodeInterface $department): bool;

}
