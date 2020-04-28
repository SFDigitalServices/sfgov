<?php

namespace Drupal\mandrill\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks access for displaying configuration page.
 */
class MandrillMailerAccessCheck implements AccessInterface {

  /**
   * Access check for Mandrill module configuration.
   *
   * Ensures a Mandrill API key has been provided.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    $sender = \Drupal::config('mailsystem.settings')->get('defaults')['sender'];

    return AccessResult::allowedIf(in_array($sender, ['mandrill_mail', 'mandrill_test_mail']));
  }

}
