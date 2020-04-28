<?php

/**
 * @file
 * Contains \Drupal\protected_pages\Form\ProtectedPagesSendEmailForm.
 */

namespace Drupal\protected_pages\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Egulias\EmailValidator\EmailValidator;
use Drupal\protected_pages\ProtectedPagesStorage;

/**
 * Provides send protected pages details email form.
 */
class ProtectedPagesSendEmailForm extends FormBase {

  /**
   * The protected pages storage service.
   *
   * @var \Drupal\protected_pages\ProtectedPagesStorage
   */
  protected $protectedPagesStorage;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a new ProtectedPagesSendEmailForm.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(MailManagerInterface $mail_manager, EmailValidator $email_validator, ProtectedPagesStorage $protectedPagesStorage) {
    $this->mailManager = $mail_manager;
    $this->emailValidator = $email_validator;
    $this->protectedPagesStorage = $protectedPagesStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('plugin.manager.mail'), $container->get('email.validator'), $container->get('protected_pages.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'protected_pages_send_email';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pid = NULL) {
    $config = \Drupal::config('protected_pages.settings');

    $form['send_email_box'] = array(
      '#type' => 'details',
      '#title' => $this->t('Send email'),
      '#description' => $this->t('You send details of this protected page by email to multiple users. Please click <a href="@here">here</a> to configure email settings.', [
        '@here' => Url::fromUri('internal:/admin/config/system/protected_pages/settings', array('query' => $this->getDestinationArray()))
          ->toString(),
      ]),
      '#open' => TRUE,
    );

    $form_state->set('pid', $pid);
    $form['send_email_box']['recipents'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Recipents'),
      '#rows' => 5,
      '#description' => $this->t('Enter enter comma separated list of recipents.'),
      '#required' => TRUE,
    );
    $form['send_email_box']['subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('email.subject'),
      '#description' => $this->t('Enter subject of email.'),
      '#required' => TRUE,
    );
    $form['send_email_box']['body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Email Body'),
      '#rows' => 15,
      '#default_value' => $config->get('email.body'),
      '#description' => $this->t('Enter the body of the email. Only [protected-page-url] and [site-name] tokens are available.
            Since password is encrypted, therefore we can not provide it by token.'),
      '#required' => TRUE,
    );

    $form['send_email_box']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send Email'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $emails = explode(',', str_replace(array("\r", "\n"), ',', $form_state->getValue('recipents')));
    foreach ($emails as $key => $email) {
      $email = trim($email);
      if ($email) {
        if (!$this->emailValidator->isValid($email)) {
          $form_state->setErrorByName('recipents', $this->t('Invalid email address: @mail. Please correct this email.', array('@mail' => $email)));
          unset($emails[$key]);
        }
        else {
          $emails[$key] = $email;
        }
      }
      else {
        unset($emails[$key]);
      }
    }
    $form_state->set('validated_recipents', implode(', ', $emails));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $fields = array('path');
    $conditions = array();
    $conditions['general'][] = array(
      'field' => 'pid',
      'value' => $form_state->get('pid'),
      'operator' => '=',
    );

    $path = $this->protectedPagesStorage->loadProtectedPage($fields, $conditions, TRUE);
    $module = 'protected_pages';
    $key = 'protected_pages_details_mail';
    $to = $form_state->get('validated_recipents');
    $from = \Drupal::config('system.site')->get('mail');
    $language_code = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $send = TRUE;
    $params = array();
    $params['subject'] = $form_state->getValue('subject');
    $params['body'] = $form_state->getValue('body');
    $params['protected_page_url'] = Url::fromUri('internal:' . $path, ['absolute' => TRUE])
        ->toString();
    $result = $this->mailManager->mail($module, $key, $to, $language_code, $params, $from, $send);
    if ($result['result'] !== TRUE) {
      $message = $this->t('There was a problem sending your email notification to @email.', array('@email' => $to));
      drupal_set_message($message, 'error');
      \Drupal::logger('protected_pages')->error($message);
    }
    else {
      $message = t('The Email has been sent to @email.', array('@email' => $to));
      drupal_set_message($message);
      \Drupal::logger('protected_pages')->notice($message);
    }

    $form_state->setRedirect('protected_pages_list');
  }

}
