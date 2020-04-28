<?php

namespace Drupal\mandrill\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks access for displaying configuration page.
 */
class MandrillConfigurationAccessCheck implements AccessInterface {

  /**
   * Access check for Mandrill module configuration.
   *
   * Ensures a Mandrill API key has been provided.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $config = \Drupal::configFactory()->getEditable('mandrill.settings');
    $api_key = $config->get('mandrill_api_key');

    return AccessResult::allowedIf(!empty($api_key));
  }

}
