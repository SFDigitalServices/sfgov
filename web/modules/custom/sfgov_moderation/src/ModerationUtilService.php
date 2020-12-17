<?php

namespace Drupal\sfgov_moderation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
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
  public function accountBelongsToDepartment(AccountInterface $account, NodeInterface $department): bool {
    $department_group = $this->getDepartmentGroupFromNode($department);
    if (!$department_group) {
      return TRUE;
    }

    return $department_group->getMember($account) ? TRUE : FALSE;
  }

  /**
   * Given a department node, get the corresponding department group.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\group\Entity\Group|null
   *   The group or null.
   */
  protected function getDepartmentGroupFromNode(NodeInterface $node): ?Group {
    // Make sure group entities are always referencing the english translation.
    $department_node = $node;
    if ($node->language()->getId() != 'en' && $node->hasTranslation('en')) {
      $department_node = $node->getTranslation('en');
    }

    /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_storage */
    $group_storage = $this->entityTypeManager->getStorage('group');

    // Search for group of this department node.
    $query = $group_storage->getQuery()
      ->condition('field_department', $department_node->id())
      ->range(0, 1)
      ->accessCheck(FALSE);
    $ids = $query->execute();

    return empty($ids) ? NULL : $group_storage->load(reset($ids));
  }
}
