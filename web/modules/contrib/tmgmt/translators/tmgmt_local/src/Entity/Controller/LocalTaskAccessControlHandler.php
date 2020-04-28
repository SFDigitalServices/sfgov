<?php

namespace Drupal\tmgmt_local\Entity\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the task entity.
 *
 * @see \Drupal\tmgmt_local\Entity\LocalTask.
 */
class LocalTaskAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete') {
      return AccessResult::forbidden();
    }
    if ($account->hasPermission('administer tmgmt') || $account->hasPermission('administer translation tasks')) {
      // Administrators can do everything.
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'provide translation services');

      // Custom operations.
      case 'unassign':
        return AccessResult::allowedIf($entity->tuid->target_id == $account->id() && $account->hasPermission('provide translation services'));
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if ($operation == 'edit') {
      return AccessResult::allowedIfHasPermissions($account, ['administer tmgmt', 'administer translation tasks']);
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer translation tasks')->orIf(AccessResult::allowedIfHasPermission($account, 'administer tmgmt'));
  }

}
