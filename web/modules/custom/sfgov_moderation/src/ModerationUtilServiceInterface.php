<?php

namespace Drupal\sfgov_moderation;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Interface ModerationUtilServiceInterface.
 */
interface ModerationUtilServiceInterface {

  /**
   * The field name that contains the departments a user belongs to.
   */
  public const DEPARTMENTS_ACCOUNT_FIELD = 'field_departments';

  /**
   * Get the department field name given the node bundle.
   *
   * @param string $bundle
   *   The node bundle.
   *
   * @return string|null
   *   The field name.
   */
  public function getDepartmentFieldName(string $bundle): ?string;

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

  /**
   * Get a list of valid reviewer user IDs given a list of department NIDs.
   *
   * @param int[] $departmentIds
   *   A list of department node IDs.
   *
   * @return int[]
   *   The list of reviewers UIDs.
   */
  public function getValidReviewers(array $departmentIds = []): array;

}
