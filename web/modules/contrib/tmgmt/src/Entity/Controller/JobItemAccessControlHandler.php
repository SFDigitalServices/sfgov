<?php

namespace Drupal\tmgmt\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access control handler for the job item entity.
 *
 * @see \Drupal\tmgmt\Entity\Job.
 */
class JobItemAccessControlHandler extends JobAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'delete') {
      return AccessResult::allowedIf($entity->isInactive());
    }
    else if ($operation == 'abort') {
      return AccessResult::allowedIf($entity->isActive() || $entity->isNeedsReview());
    }
    else if ($entity->getJob()) {
      return $entity->getJob()->access($operation, $account, TRUE);
    }
  }

}
