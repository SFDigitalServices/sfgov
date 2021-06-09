<?php

namespace Drupal\sfgov_moderation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

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
   * The account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ModerationUtilService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $currentUser) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
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
  public function accountBelongsToDepartment(AccountInterface $account, $department): bool {
    // Get full user object, not just the session.
    $accountEntity = $this->entityTypeManager->getStorage('user')->load($account->id());

    $accountDepartments = [];
    foreach ($accountEntity->get(static::DEPARTMENTS_ACCOUNT_FIELD)->getValue() as $item) {
      $accountDepartments[] = $item['target_id'];
    }

    return in_array(is_object($department) ? $department->id() : $department, $accountDepartments);
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

    // Remove current user from the reviewer options list.
    $current_user_id = $this->currentUser->id();
    unset($ids[$current_user_id]);

    return $ids ? array_values($ids) : [];
  }

  /**
   * @inheritDoc
   */
  public function canPublishFromDraftWithoutReviewer(AccountInterface $account, array $departmentIds): bool {
    if (!in_array(static::PUBLISHER_ROLE, $account->getRoles())) {
      return FALSE;
    }

    $belongsTo = FALSE;
    foreach ($departmentIds as $departmentId) {
      if ($this->accountBelongsToDepartment($account, $departmentId)) {
        $belongsTo = TRUE;
        break;
      }
    }
    if (!$belongsTo) {
      return FALSE;
    }

    return TRUE;
  }


  /**
   * @inheritDoc
   */
  public function getModerationFields($node):array {

    /** @var \Drupal\node\Entity\Node $revision */
    $revision = $this->getLatestRevision($node);

    // Get State.
    $state = $revision->moderation_state->getValue();

    // Get Reviewer.
    $reviewer = $revision->reviewer->getValue();
    if (isset($reviewer[0]['target_id'])) {
      $account = User::load($reviewer[0]['target_id']);
      $username = $account->getUsername();
    }

    // Get Department.
    $bundle = $revision->bundle();
    $dept_field_name = $this->getDepartmentFieldName($bundle);
    $department_labels = '';

    if ($revision->hasField($dept_field_name)) {
      $departments = $revision->get($dept_field_name)->getValue();

      if (isset($departments)) {


        foreach($departments as $i => $department) {
          $node = Node::load($department['target_id']);
          $department_label = $node->label();

          $department_labels .= $department_label . ', ' ;
        }

      }
    }

    return [
      'state' => isset($state[0]['value']) ? $state[0]['value'] : '',
      'username' => $username ?? NULL,
      'department' => $department_labels,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getLatestRevision($node): Node {

    $nid = $node->id();

    $vid = $this->entityTypeManager
      ->getStorage('node')
      ->getLatestRevisionId($nid);

    /** @var \Drupal\node\Entity\Node $revision */
    $revision = $this->entityTypeManager
      ->getStorage('node')
      ->loadRevision($vid);

    return $revision;
  }

}
