<?php

/**
 * @file
 * Contains \Drupal\protected_pages\Form\ProtectedPagesSettingForm.
 */

namespace Drupal\protected_pages\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Egulias\EmailValidator\EmailValidator;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides protected pages settings configuration form.
 */
class ProtectedPagesSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['protected_pages.settings'];
  }

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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Provides the password hashing service object.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $password;

  /**
   * Constructs a new ProtectedPagesSettingForm.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password hashing service.
   */
  public function __construct(MailManagerInterface $mail_manager, EmailValidator $email_validator, RendererInterface $renderer, PasswordInterface $password) {
    $this->mailManager = $mail_manager;
    $this->emailValidator = $email_validator;
    $this->renderer = $renderer;
    $this->password = $password;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('plugin.manager.mail'), $container->get('email.validator'), $container->get('renderer'), $container->get('password')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'protected_pages_settings';
  }

  /**
   * Form element validation handler to validate session expire time value..
   */
  public function protectedPagesValidateIntegerPositive($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value < 0)) {
      $form_state->setError($element, $this->t('%name must be a positive integer.', array('%name' => $element['#title'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('protected_pages.settings');

    $form['protected_pages_password_fieldset'] = array(
      '#type' => 'details',
      '#title' => $this->t('Protected Pages Password Settings'),
      '#description' => $this->t('Configure password related settings.'),
      '#open' => TRUE,
    );
    $global_password_help_text = array();

    $global_password_help_text[] = $this->t('Allow per page password = Only per page password will be accepted. Global password will not be accepted.');
    $global_password_help_text[] = $this->t('Allow per page password or Global password = Per page  password and global password both will be accepted.');
    $global_password_help_text[] = $this->t('Allow Only Global = Only global password will be accepted for each protected page.');
    $global_password_help_text_list = [
      '#theme' => 'item_list',
      '#items' => $global_password_help_text,
      '#title' => $this->t('Please select the appropriate option for protected pages handling.'),
    ];
    $form['protected_pages_password_fieldset']['protected_pages_user_global_password'] = array(
      '#type' => 'select',
      '#title' => t('Global Password Setting'),
      '#default_value' => $config->get('password.per_page_or_global'),
      '#options' => array(
        'per_page_password' => $this->t('Allow per page password'),
        'per_page_or_global' => $this->t('Allow per page password or Global password'),
        'only_global' => $this->t('Allow Only Global'),
      ),
      '#description' => $this->renderer->render($global_password_help_text_list),
    );

    $form['protected_pages_password_fieldset']['protected_pages_global_password_field'] = array(
      '#type' => 'password_confirm',
      '#title' => $this->t('Global Password'),
      '#description' => $this->t('The default password for all protected pages. This
                                password is necessary if you select the previous checkbox "Allow per page
                                password or Global password" or "Allow Only Global" options above.'),
    );

    $form['protected_pages_password_fieldset']['protected_pages_session_expire_time'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Session Expire Time'),
      '#description' => $this->t('When user enters password a session is created.
      The node will be accessible until session expire. Once session expires,
      user will need to enter password again. The default session expire time
      is 0 (unlimited).'),
      '#default_value' => $config->get('password.protected_pages_session_expire_time'),
      '#required' => TRUE,
      '#size' => 10,
      '#element_validate' => array(
        array(
          $this,
          'protectedPagesValidateIntegerPositive',
        ),
      ),
      '#field_suffix' => $this->t('minutes'),
    );

    $form['protected_pages_email_fieldset'] = array(
      '#type' => 'details',
      '#title' => $this->t('Protected pages email settings'),
      '#description' => $this->t('The following settings allows admin to send emails to multiple users about protected pages details to access protected pages.'),
      '#open' => TRUE,
    );

    $form['protected_pages_email_fieldset']['protected_pages_email_subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#default_value' => $config->get('email.subject'),
      '#description' => $this->t('Enter the subject of the email.'),
    );

    $form['protected_pages_email_fieldset']['protected_pages_email_body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Email content'),
      '#rows' => 15,
      '#default_value' => $config->get('email.body'),
      '#description' => $this->t('Enter the body of the email. Only [protected-page-url] and [site-name] tokens are available.
      Since password is encrypted, therefore we can not provide it by token.'),
    );

    $form['protected_pages_other_fieldset'] = array(
      '#type' => 'details',
      '#title' => $this->t('Protected Pages Other Settings'),
      '#description' => $this->t('Configure other settings.'),
      '#open' => TRUE,
    );

    $form['protected_pages_other_fieldset']['protected_pages_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Password page title'),
      '#default_value' => $config->get('others.protected_pages_title'),
      '#description' => $this->t('Enter the title of the protected page.'),
    );

    $form['protected_pages_other_fieldset']['protected_pages_description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Password page description (inside the field set)'),
      '#default_value' => $config->get('others.protected_pages_description'),
      '#description' => $this->t('Enter specific description for the protected page. This description is displayed inside the fieldset. HTML is accepted.'),
    );

    $form['protected_pages_other_fieldset']['protected_pages_password_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Password field label'),
      '#default_value' => $config->get('others.protected_pages_password_label'),
      '#description' => $this->t('Enter the text for the password field label.'),
    );
    $form['protected_pages_other_fieldset']['protected_pages_submit_button_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Submit Button Text'),
      '#default_value' => $config->get('others.protected_pages_submit_button_text'),
      '#description' => $this->t('Enter the text for the submit button of enter password form.'),
    );
    $form['protected_pages_other_fieldset']['protected_pages_incorrect_password_msg'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Incorrect Password Error Text'),
      '#default_value' => $config->get('others.protected_pages_incorrect_password_msg'),
      '#description' => $this->t('This error text will appear if someone enters wrong password in "Enter password screen".'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('protected_pages.settings');
    $config->set('password.per_page_or_global', $form_state->getValue('protected_pages_user_global_password'));
    $config->set('password.protected_pages_session_expire_time', $form_state->getValue('protected_pages_session_expire_time'));
    $config->set('email.subject', $form_state->getValue('protected_pages_email_subject'));
    $config->set('email.body', $form_state->getValue('protected_pages_email_body'));
    $config->set('others.protected_pages_title', $form_state->getValue('protected_pages_title'));
    $config->set('others.protected_pages_description', $form_state->getValue('protected_pages_description'));
    $config->set('others.protected_pages_password_label', $form_state->getValue('protected_pages_password_label'));
    $config->set('others.protected_pages_submit_button_text', $form_state->getValue('protected_pages_submit_button_text'));
    $config->set('others.protected_pages_incorrect_password_msg', $form_state->getValue('protected_pages_incorrect_password_msg'));
    $password = $form_state->getValue('protected_pages_global_password_field');
    if ($password) {
      $password_hash = $this->password->hash(Html::escape($password));
      $config->set('password.protected_pages_global_password', $password_hash);
    }
    $config->save();
    drupal_flush_all_caches();
    return parent::submitForm($form, $form_state);
  }

}
