<?php

/**
 * @file
 * Contains \Drupal\toc_api\TocTypeAccessControlHandler.
 */

namespace Drupal\toc_api;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the TOC type entity type.
 *
 * @see \Drupal\toc_api\Entity\TocTYpe.
 */
class TocTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'delete':
        return AccessResult::allowedIf($account->hasPermission('administer toc types') && $entity->id() != 'default')->cachePerPermissions();

      default:
        return AccessResult::allowedIf($account->hasPermission('administer toc types'))->cachePerPermissions();
    }

  }

}
