<?php

namespace Drupal\mimemail_example\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\mimemail\Utility\MimeMailFormatHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The example email contact form.
 */
class ExampleForm extends FormBase {

  /**
   * The email.validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new ExampleForm.
   *
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   */
  public function __construct(EmailValidatorInterface $email_validator, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager) {
    $this->emailValidator = $email_validator;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mimemail_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $dir = NULL, $img = NULL) {
    $form['intro'] = [
      '#markup' => $this->t('Use this form to send a HTML message to an email address. No spamming!'),
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#description' => $this->t('A key to identify the email sent.'),
      '#default_value' => 'test',
      '#required' => TRUE,
    ];

    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#description' => $this->t('The email address of the recipient. The formatting of this string must comply with RFC 2822.'),
      '#default_value' => $this->currentUser()->getEmail(),
      '#required' => TRUE,
    ];

    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender name'),
      '#description' => $this->t("The sender's name. Leave empty to use the site-wide configured name."),
    ];

    $form['from_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender email address'),
      '#description' => $this->t("The sender's email address. Leave empty to use the site-wide configured address."),
    ];

    $form['params'] = [
      '#tree' => TRUE,
      'headers' => [
        'Cc' => [
          '#type' => 'textfield',
          '#title' => $this->t('Cc'),
          '#description' => $this->t("The mail's carbon copy address. You may separate multiple addresses with comma."),
        ],
        'Bcc' => [
          '#type' => 'textfield',
          '#title' => $this->t('Bcc'),
          '#description' => $this->t("The mail's blind carbon copy address. You may separate multiple addresses with comma."),
        ],
        'Reply-to' => [
          '#type' => 'textfield',
          '#title' => $this->t('Reply to'),
          '#description' => $this->t("The address to reply to. Leave empty to use the sender's address."),
        ],
        'List-unsubscribe' => [
          '#type' => 'textfield',
          '#title' => $this->t('List-unsubscribe'),
          '#description' => $this->t('An email address and/or a URL which can be used for unsubscription. Values must be enclosed by angle brackets and separated by a comma.'),
        ],
      ],
      'subject' => [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#description' => $this->t("The email's subject."),
      ],
      'body' => [
        '#type' => 'textarea',
        '#title' => $this->t('HTML message'),
        '#description' => $this->t("HTML version of the email body. This will be formatted using the text format selected at 'admin/config/system/mimemail'"),
      ],
      'plain' => [
        '#type' => 'hidden',
        '#states' => [
          'value' => [
            ':input[name="body"]' => ['value' => ''],
          ],
        ],
      ],
      'plaintext' => [
        '#type' => 'textarea',
        '#title' => $this->t('Plain text message'),
        '#description' => $this->t('Plain text version of the email body. HTML not allowed.'),
      ],
      'attachments' => [
        '#name' => 'files[attachment]',
        '#type' => 'file',
        '#title' => $this->t('Choose a file to send as an email attachment'),
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send message'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Extract the address part of the entered email before trying to validate.
    // The email.validator service does not work on RFC2822 formatted addresses
    // so we need to extract the RFC822 part out first. This is not as good as
    // actually validating the full RFC2822 address, but it is better than
    // either just validating RFC822 or not validating at all.
    $pattern = '/<(.*?)>/';
    $address = $form_state->getValue('to');
    preg_match_all($pattern, $address, $matches);
    $address = isset($matches[1][0]) ? $matches[1][0] : $address;
    if (!$this->emailValidator->isValid($address)) {
      $form_state->setErrorByName('to', $this->t('That email address is not valid.'));
    }

    $file = file_save_upload('attachment', [], 'temporary://', 0);
    if ($file) {
      $form_state->setValue(['params', 'attachments'], [['filepath' => $file->getFileUri()]]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Assemble arguments for MailManager::mail().
    $module = 'mimemail_example';
    $key = $form_state->getValue('key');
    $to = $form_state->getValue('to');
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $params = $form_state->getValue('params');
    $reply = $params['headers']['Reply-to'];
    $send = TRUE;

    // Now add values to $params and/or modify submitted values.
    // Set From header.
    if (!empty($form_state->getValue('from_mail'))) {
      $params['headers']['From'] = MimeMailFormatHelper::mimeMailAddress([
        'name' => $form_state->getValue('from'),
        'mail' => $form_state->getValue('from_mail'),
      ]);
    }
    elseif (!empty($form_state->getValue('from'))) {
      $params['headers']['From'] = $from = $form_state->getValue('from');
    }
    else {
      // Empty 'from' will result in the default site email being used.
    }

    // Handle empty attachments - we require this to be an array.
    if (empty($params['attachments'])) {
      $params['attachments'] = [];
    }

    // Remove empty values from $param['headers'] - this will force the
    // the formatting mailsystem and the sending mailsystem to use the
    // default values for these elements.
    foreach ($params['headers'] as $header => $value) {
      if (empty($value)) {
        unset($params['headers'][$header]);
      }
    }

    // Finally, call MailManager::mail() to send the mail.
    $result = $this->mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
    if ($result['result'] == TRUE) {
      $this->messenger()->addMessage($this->t('Your message has been sent.'));
    }
    else {
      // This condition is also logged to the 'mail' logger channel by the
      // default PhpMail mailsystem.
      $this->messenger()->addError($this->t('There was a problem sending your message and it was not sent.'));
    }
  }

}
