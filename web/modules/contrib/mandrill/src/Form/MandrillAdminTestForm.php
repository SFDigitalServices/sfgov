<?php

namespace Drupal\mandrill\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mandrill\Plugin\Mail\MandrillMail;
use Drupal\mandrill\Plugin\Mail\MandrillTestMail;

/**
 * Form controller for the Mandrill send test email form.
 *
 * @ingroup mandrill
 */
class MandrillAdminTestForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  function getFormID() {
    return 'mandrill_test_email';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Send Test Email');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action will send a test email through Mandrill.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mandrill.test');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Send test email');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $click_tracking_url = Url::fromUri('http://www.drupal.org/project/mandrill');

    // If sending using the mandrill_test_mail service, attachments and bcc are
    // not supported.
    $mandrill_test_mail = \Drupal::config('mailsystem.settings')->get('defaults')['sender'] == 'mandrill_test_mail';

    $form['mandrill_test_address'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email address to send a test email to'),
      '#default_value' => \Drupal::config('system.site')->get('mail'),
      '#description' => $this->t('Type in an address to have a test email sent there.'),
      '#required' => TRUE,
    );

    // If sending using the mandrill_test_mail service, bcc is not
    // supported so we hide the "Email address to BCC on this test email" text
    // input field.
    if (!$mandrill_test_mail) {
      $form['mandrill_test_bcc_address'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Email address to BCC on this test email'),
        '#description' => $this->t('Type in an address to have a test email sent there.'),
      );
    }

    $form['mandrill_test_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Test body contents'),
      '#default_value' => $this->t('If you receive this message it means your site is capable of using Mandrill to send email. This url is here to test click tracking: %link',
        array('%link' => Link::fromTextAndUrl($this->t('link'), $click_tracking_url)->toString())),
    );

    // If sending using the mandrill_test_mail service, attachments are not
    // supported so we hide the "Include attachment" checkbox.
    if (!$mandrill_test_mail) {
      $form['include_attachment'] = array(
        '#title' => $this->t('Include attachment'),
        '#type' => 'checkbox',
        '#description' => $this->t('If checked, the Drupal icon will be included as an attachment with the test email.'),
        '#default_value' => TRUE,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = array(
      'id' => 'mandrill_test_email',
      'module' => 'mandrill',
      'to' => $form_state->getValue('mandrill_test_address'),
      'body' => $form_state->getValue('mandrill_test_body'),
      'subject' => $this->t('Drupal Mandrill test email'),
    );

    $bcc_email = $form_state->getValue('mandrill_test_bcc_address');

    if (!empty($bcc_email)) {
      $message['bcc_email'] = $bcc_email;
    }

    if ($form_state->getValue('include_attachment')) {
      $message['attachments'][] = \Drupal::service('file_system')->realpath('core/themes/bartik/logo.svg');
      $message['body'] .= ' ' . $this->t('The Drupal icon is included as an attachment to test the attachment functionality.');
    }

    // Get Mandrill mailer service specified in Mailsystem settings.
    // This service will either be mandrill_mail or mandrill_test_mail or the
    // route that exposes this form won't even show up - see
    // MandrillMailerAccessCheck.php.
    $sender = \Drupal::config('mailsystem.settings')->get('defaults')['sender'];
    if ($sender == 'mandrill_mail') {
      /* @var $mandrill \Drupal\mandrill\Plugin\Mail\MandrillMail */
      $mailer = new MandrillMail();
    }
    elseif ($sender == 'mandrill_test_mail') {
      /* @var $mandrill \Drupal\mandrill\Plugin\Mail\MandrillTestMail */
      $mailer = new MandrillTestMail();
    }

    // Ensure we have a mailer and send the message.
    if (isset($mailer) && $mailer->mail($message)) {
      drupal_set_message($this->t('Test email has been sent.'));
    }
  }

}
