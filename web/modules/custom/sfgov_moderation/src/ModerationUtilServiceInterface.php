<?php

namespace Drupal\sfgov_moderation;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Interface for Moderation utilities.
 */
interface ModerationUtilServiceInterface {

  /**
   * The field name that contains the departments a user belongs to.
   */
  public const DEPARTMENTS_ACCOUNT_FIELD = 'field_departments';

  /**
   * The publisher role machine name.
   */
  public const PUBLISHER_ROLE = 'publisher';

  /**
   * The writer role machine name.
   */
  public const WRITER_ROLE = 'writer';

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
   * @param \Drupal\node\NodeInterface|int $department
   *   The department node or a department node ID.
   *
   * @return bool
   *   If the node belongs to a department, returns true or false. True if the
   *   node has no department assigned.
   */
  public function accountBelongsToDepartment(AccountInterface $account, $department): bool;

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

  /**
   * Check if an account can publish from draft without selecting a reviewer.
   *
   * @todo Maybe change this to a permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The given account.
   * @param array $departmentIds
   *   A list of department node IDs.
   *
   * @return bool
   *   True if the user has the publisher role, and they belong to one of the
   *   departments.
   */
  public function canPublishFromDraftWithoutReviewer(AccountInterface $account, array $departmentIds): bool;

  /**
   * Check if an account can modify department node.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The given account.
   *
   * @return bool
   *   True if the user has a role that can modify departments.
   */
  public function canModifyDepartment(AccountInterface $account):bool;

  /**
   * Get moderation state of most recent revision by nid.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node object.
   *
   * @return array
   *   An array of revision values.
   */
  public function getModerationFields(Node $node): array;

  /***
   * Gets the latest node revision.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A (typically default revision) node object.
   *
   * @return \Drupal\node\Entity\Node
   *   The latest revision's node object.
   */
  public function getLatestRevision(Node $node): Node;

}
