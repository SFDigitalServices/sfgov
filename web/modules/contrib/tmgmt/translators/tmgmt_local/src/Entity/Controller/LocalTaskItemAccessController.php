<?php

namespace Drupal\tmgmt_local\Entity\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the task item entity.
 *
 * @see \Drupal\tmgmt_local\Entity\LocalTaskItem.
 */
class LocalTaskItemAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view' || $operation == 'update') {
      if ($account->hasPermission('administer tmgmt') || $account->hasPermission('administer translation tasks')) {
        // Administrators can do everything.
        return AccessResult::allowed()->cachePerPermissions();
      }
      return AccessResult::allowedIf($entity->getTask()->tuid->target_id == $account->id() && $account->hasPermission('provide translation services'));
    }
    return $entity->getTask()->access($operation, $account, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer translation tasks')->orIf(AccessResult::allowedIfHasPermission($account, 'administer tmgmt'));
  }

}
