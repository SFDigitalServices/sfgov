<?php

namespace Drupal\sfgov_moderation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Class ModerationUtilService.
 */
class ModerationUtilService implements ModerationUtilServiceInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ModerationUtilService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @inheritDoc
   */
  public function getDepartmentFieldName(string $bundle): ?string {
    switch ($bundle) {
      case 'transaction':
      case 'topic':
      case 'public_body':
      case 'location':
        return 'field_departments';
        break;

      case 'news':
      case 'event':
      case 'information_page':
      case 'meeting':
      case 'campaign':
      case 'department_table':
      case 'form_confirmation_page':
      case 'page':
      case 'person':
      case 'resource_collection':
      case 'step_by_step':
        return 'field_dept';
        break;
    }

    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function accountBelongsToDepartment(AccountInterface $account, NodeInterface $department): bool {
    // Get full user object, not just the session.
    $accountEntity = $this->entityTypeManager->getStorage('user')->load($account->id());

    $accountDepartments = [];
    foreach ($accountEntity->get(static::DEPARTMENTS_ACCOUNT_FIELD)->getValue() as $item) {
      $accountDepartments[] = $item['target_id'];
    }

    return in_array($department->id(), $accountDepartments);
  }

  /**
   * @inheritDoc
   */
  public function getValidReviewers(array $departmentIds = []): array {
    if (empty($departmentIds)) {
      return [];
    }

    $query = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->condition('uid', 0, '>')
      ->condition('status', 1)
      ->condition(ModerationUtilServiceInterface::DEPARTMENTS_ACCOUNT_FIELD, $departmentIds, 'IN')
      ->sort('name', 'DESC');

    $ids = $query->execute();
    return $ids ? array_values($ids) : [];
  }

}
