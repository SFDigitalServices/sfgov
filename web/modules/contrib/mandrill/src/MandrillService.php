<?php

namespace Drupal\mandrill;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Mandrill Service.
 */
class MandrillService implements MandrillServiceInterface {

  /**
   * The Mandrill API service.
   *
   * @var \Drupal\mandrill\MandrillAPIInterface
   */
  protected $mandrill_api;

  /**
   * The Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Logger Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $log;

  /**
   * Constructs the service.
   *
   * @param \Drupal\mandrill\MandrillAPIInterface $mandrill_api
   *   The Mandrill API service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory.
   */
  public function __construct(MandrillAPIInterface $mandrill_api, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->mandrill_api = $mandrill_api;
    $this->config = $config_factory;
    $this->log = $logger_factory->get('mandrill');
  }

  /**
   * Get the mail systems defined in the mail system module.
   *
   * @return array
   *   Array of mail systems and keys
   *   - key Either the module-key or default for site wide system.
   *   - sender The class to use for sending mail.
   *   - formatter The class to use for formatting mail.
   */
  public function getMailSystems() {
    $systems = [];
    // Check if the system wide sender or formatter is Mandrill.
    $mailSystemConfig = $this->config->get('mailsystem.settings');
    $systems[] = [
      'key' => 'default',
      'sender' => $mailSystemConfig->get('defaults')['sender'],
      'formatter' => $mailSystemConfig->get('defaults')['formatter'],
    ];
    // Check all custom configured modules if any uses Mandrill.
    $modules = $mailSystemConfig->get('modules') ?: [];
    foreach ($modules as $module => $configuration) {
      foreach ($configuration as $key => $settings) {
        $systems[] = [
          'key' => "$module-$key",
          'sender' => $settings['sender'],
          'formatter' => $settings['formatter'],
        ];
      }
    }
    return $systems;
  }

  /**
   * Helper to generate an array of recipients.
   *
   * This function accepts an array of values keyed in the following way:
   * $receiver = [
   *   'to' => 'user@domain.com, any number of names <user@domain.com>',
   *   'cc' => 'user@domain.com, any number of names <user@domain.com>',
   *   'bcc' => 'user@domain.com, any number of names <user@domain.com>',
   * ];
   * The only required key is 'to'. The other values will automatically be
   * discovered if present. The strings of email addresses could provide a
   * single email address or many, depending on the needs of the application.
   *
   * This structure is in keeping with the Mandrill API documentation located
   * here: https://mandrillapp.com/api/docs/messages.html.
   *
   * @param mixed $receivers
   *   An array of comma delimited lists of email addresses.
   *
   * @return array
   *   array of email addresses
   */
  public function getReceivers($receivers) {
    // Check the input variable type to provide backward compatibility for
    // when only a string of 'to' recipients are passed.
    if (is_string($receivers)) {
      $receivers = [
        'to' => $receivers,
      ];
    }
    $recipients = [];
    foreach ($receivers as $type => $receiver) {
      $receiver_array = explode(',', $receiver);
      foreach ($receiver_array as $email) {
        if (preg_match(MANDRILL_EMAIL_REGEX, $email, $matches)) {
          $recipients[] = [
            'email' => $matches[2],
            'name' => $matches[1],
            'type' => $type,
          ];
        }
        else {
          $recipients[] = [
            'email' => $email,
            'type' => $type,
          ];
        }
      }
    }
    return $recipients;
  }

  /**
   * Abstracts sending of messages, allowing queueing option.
   *
   * @param array $message
   *   A message array formatted for Mandrill's sending API.
   *
   * @return bool
   *   TRUE if no exception thrown.
   */
  public function send($message) {
    try {
      $response = $this->mandrill_api->send($message);

      return $this->handleSendResponse($response, $message);
    }
    catch (\Exception $e) {
      $this->log->error('Error sending email from %from to %to. @code: @message', array(
        '%from' => $message['from_email'],
        '%to' => $message['to'],
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ));
      return FALSE;
    }
  }

  /**
   * Response handler for sent messages.
   *
   * @param array $response
   *   Response from the Mandrill API.
   * @param array $message
   *   The sent message.
   *
   * @return bool
   *   TRUE if the message was sent or queued without error.
   */
  protected function handleSendResponse($response, $message) {
    if (!isset($response['status'])) {
      foreach ($response as $result) {
        // Allow other modules to react based on a send result.
        \Drupal::moduleHandler()->invokeAll('mandrill_mailsend_result', [$result]);
        switch ($result['status']) {
          case "error":
          case "invalid":
          case "rejected":
            $to = isset($result['email']) ? $result['email'] : 'recipient';
            $status = isset($result['status']) ? $result['status'] : 'message';
            $error_message = isset($result['message']) ? $result['message'] : 'no message';
            if (!isset($result['message']) && isset($result['reject_reason'])) {
              $error_message = $result['reject_reason'];
            }

            $this->log->error('Failed sending email from %from to %to. @status: @message', array(
              '%from' => $message['from_email'],
              '%to' => $to,
              '@status' => $status,
              '@message' => $error_message,
            ));
            return FALSE;
          case "queued":
            $this->log->info('Email from %from to %to queued by Mandrill App.', array(
              '%from' => $message['from_email'],
              '%to' => $result['email'],
            ));
            break;
        }
      }
    }
    else {
      $this->log->warning('Mail send failed with status %status: code %code, %name, %message', array(
        '%status' => $response['status'],
        '%code' => $response['code'],
        '%name' => $response['name'],
        '%message' => $response['message'],
      ));
      return FALSE;
    }
    return TRUE;
  }

}
