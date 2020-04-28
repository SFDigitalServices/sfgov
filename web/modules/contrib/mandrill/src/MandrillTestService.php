<?php

namespace Drupal\mandrill;

/**
 * Test Mandrill service.
 */
class MandrillTestService extends MandrillService {

  /**
   * {@inheritdoc}
   */
  protected function handleSendResponse($response, $message) {
    if (isset($response['status'])) {
      // There was a problem sending the message.
      return FALSE;
    }

    foreach ($response as $result) {
      // Allow other modules to react based on a send result.
      \Drupal::moduleHandler()->invokeAll('mandrill_mailsend_result', [$result]);
      switch ($result['status']) {
        case "error":
        case "invalid":
        case "rejected":
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Abstracts sending of messages using PHP's built-in mail() function
   * instead of Mandrill's sending API.
   *
   * @param array $message
   *   A message array formatted for Mandrill's sending API.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted for delivery, FALSE otherwise.
   */
  public function send($message) {
    if (\Drupal::config('system.mail')->get('interface.default') === 'test_mail_collector') {
      $captured_emails = \Drupal::state()->get('system.test_mail_collector') ?: [];
      $captured_emails[] = $message;
      \Drupal::state()->set('system.test_mail_collector', $captured_emails);
      return TRUE;
    }
    return parent::mail($message);
  }

}
