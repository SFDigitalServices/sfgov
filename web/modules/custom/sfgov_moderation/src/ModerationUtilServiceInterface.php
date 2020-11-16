<?php

namespace Drupal\sfgov_moderation;

use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Interface ModerationUtilServiceInterface.
 */
interface ModerationUtilServiceInterface {

  /**
   * Check if an account belongs to a department.
   *
   * @param \Drupal\user\UserInterface $account
   *   The given account.
   * @param \Drupal\node\NodeInterface $department
   *   The department node.
   *
   * @return bool
   *   True if account belongs, false if not.
   */
  public function accountBelongsToDepartment(UserInterface $account, NodeInterface $department): bool;

}
