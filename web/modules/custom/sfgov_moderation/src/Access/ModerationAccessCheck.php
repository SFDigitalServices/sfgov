<?php

namespace Drupal\sfgov_moderation\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;


class ModerationAccessCheck implements AccessInterface {

  public function access(AccountInterface $account) {

    // @todo Only target translation edit pages.
    // @todo Use dynamic permissions like `translate [content type] node`
    return (!$account->hasPermission('create translations')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
