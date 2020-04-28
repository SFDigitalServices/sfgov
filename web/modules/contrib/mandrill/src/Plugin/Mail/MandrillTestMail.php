<?php

namespace Drupal\mandrill\Plugin\Mail;

/**
 * Mandrill test mail plugin.
 *
 * @Mail(
 *   id = "mandrill_test_mail",
 *   label = @Translation("Mandrill test mailer"),
 *   description = @Translation("Sends test messages through Mandrill.")
 * )
 */
class MandrillTestMail extends MandrillMail {

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();

    $this->mandrill = \Drupal::service('mandrill.test.service');
  }

}
