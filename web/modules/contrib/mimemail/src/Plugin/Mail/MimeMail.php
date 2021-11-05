<?php

namespace Drupal\mimemail\Plugin\Mail;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\mimemail\Utility\MimeMailFormatHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the default Drupal mail backend, using PHP's native mail() function.
 *
 * @Mail(
 *   id = "mime_mail",
 *   label = @Translation("Mime Mail mailer"),
 *   description = @Translation("Sends MIME-encoded emails with embedded images and attachments.")
 * )
 */
class MimeMail extends PhpMail implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The email.validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * MimeMail plugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, EmailValidatorInterface $email_validator, RendererInterface $renderer) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->emailValidator = $email_validator;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('email.validator'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    if (is_array($message['body'])) {
      $message['body'] = implode("\n\n", $message['body']);
    }

    if (preg_match('/plain/', $message['headers']['Content-Type'])) {
      if (!$format = $this->configFactory->get('mimemail.settings')->get('format')) {
        $format = filter_fallback_format();
      }
      $message['body'] = check_markup($message['body'], $format);
    }

    $message = $this->prepareMessage($message);

    return $message;
  }

  /**
   * Prepares the message for sending.
   *
   * @param array $message
   *   An array containing the message data. The optional parameters are:
   *   - plain: Whether to send the message as plaintext only or HTML. If
   *     this evaluates to TRUE the message will be sent as plaintext.
   *   - plaintext: Optional plaintext portion of a multipart email.
   *   - attachments: An array of arrays which describe one or more attachments.
   *     Existing files can be added by path, dynamically-generated files can be
   *     added by content. The internal array contains the following elements:
   *      - filepath: Relative Drupal path to an existing file
   *        (filecontent is NULL).
   *      - filecontent: The actual content of the file (filepath is NULL).
   *      - filename: The filename of the file.
   *      - filemime: The MIME type of the file.
   *      The array of arrays looks something like this:
   *      Array
   *      (
   *        [0] => Array
   *        (
   *         [filepath] => '/sites/default/files/attachment.txt'
   *         [filecontent] => 'My attachment.'
   *         [filename] => 'attachment.txt'
   *         [filemime] => 'text/plain'
   *        )
   *      )
   *
   * @return array
   *   All details of the message.
   */
  protected function prepareMessage(array $message) {
    $module = $message['module'];
    $key = $message['key'];
    $to = $message['to'];
    $from = $message['from'];
    $subject = $message['subject'];
    $body = $message['body'];

    $headers = isset($message['params']['headers']) ? $message['params']['headers'] : [];
    $plain = isset($message['params']['plain']) ? $message['params']['plain'] : NULL;
    $plaintext = isset($message['params']['plaintext']) ? $message['params']['plaintext'] : NULL;
    $attachments = isset($message['params']['attachments']) ? $message['params']['attachments'] : [];

    $site_name = $this->configFactory->get('system.site')->get('name');
    $site_mail = $this->configFactory->get('system.site')->get('mail');
    //$site_mail = variable_get('site_mail', ini_get('sendmail_from'));
    $simple_address = $this->configFactory->get('mimemail.settings')->get('simple_address');

    // Override site mails default sender.
    if ((empty($from) || $from == $site_mail)) {
      $mimemail_name = $this->configFactory->get('mimemail.settings')->get('name');
      $mimemail_mail = $this->configFactory->get('mimemail.settings')->get('mail');
      $from = [
        'name' => !empty($mimemail_name) ? $mimemail_name : $site_name,
        'mail' => !empty($mimemail_mail) ? $mimemail_mail : $site_mail,
      ];
    }

    if (empty($body)) {
      // Body is empty, this is a plaintext message.
      $plain = TRUE;
    }
    // Try to determine recipient's text mail preference.
    elseif (is_null($plain)) {
      if (is_object($to) && isset($to->data['mimemail_textonly'])) {
        $plain = $to->data['mimemail_textonly'];
      }
      elseif (is_string($to) && $this->emailValidator->isValid($to)) {
        if (is_object($account = user_load_by_mail($to)) && isset($account->data['mimemail_textonly'])) {
          $plain = $account->data['mimemail_textonly'];
          // Might as well pass the user object to the address function.
          $to = $account;
        }
      }
    }

    // Removing newline character introduced by _drupal_wrap_mail_line().
    $subject = str_replace(["\n"], '', trim(MailFormatHelper::htmlToText($subject)));

    $body = [
      '#theme' => 'mimemail_message',
      '#module' => $module,
      '#key' => $key,
      '#recipient' => $to,
      '#subject' => $subject,
      '#body' => $body,
    ];

    $body = $this->renderer->renderPlain($body);

    /*foreach (module_implements('mail_post_process') as $module) {
      $function = $module . '_mail_post_process';
      $function($body, $key);
    }*/

    $plain = $plain || $this->configFactory->get('mimemail.settings')->get('textonly');
    $from = MimeMailFormatHelper::mimeMailAddress($from);
    $mail = MimeMailFormatHelper::mimeMailHtmlBody($body, $subject, $plain, $plaintext, $attachments);
    $headers = array_merge($message['headers'], $headers, $mail['headers']);

    $message['to'] = MimeMailFormatHelper::mimeMailAddress($to, $simple_address);
    $message['from'] = $from;
    $message['subject'] = $subject;
    $message['body'] = $mail['body'];
    $message['headers'] = MimeMailFormatHelper::mimeMailHeaders($headers, $from);

    return $message;
  }

}
