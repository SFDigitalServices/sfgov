<?php

/**
 * @file
 * Contains \Drupal\protected_pages\Form\ProtectedPagesLoginForm.
 */

namespace Drupal\protected_pages\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\protected_pages\ProtectedPagesStorage;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Component\Utility\Html;

/**
 * Provides login screen to access protected page.
 */
class ProtectedPagesLoginForm extends FormBase {

  /**
   * The protected pages storage service.
   *
   * @var \Drupal\protected_pages\ProtectedPagesStorage
   */
  protected $protectedPagesStorage;

  /**
   * Provides the password hashing service object.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $password;

  /**
   * Constructs a new ProtectedPagesLoginForm.
   *
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password hashing service.
   */
  public function __construct(PasswordInterface $password, ProtectedPagesStorage $protectedPagesStorage) {

    $this->password = $password;
    $this->protectedPagesStorage = $protectedPagesStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('password'), $container->get('protected_pages.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'protected_pages_enter_password';
  }

  /**
   * Checks access based permission and protected page id.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessProtectedPageLoginScreen() {
    $account = \Drupal::currentUser();

    $param_protected_page = $this->getRequest()->query->get('protected_page');
    $param_exists = (isset($param_protected_page) && is_numeric($param_protected_page));
    return AccessResult::allowedIf(($account->hasPermission('access protected page password screen') || ($account->id() == 1)) && $param_exists);
  }

  /**
   * Route title callback.
   *
   * @return string
   *   The protected page login screen title.
   */
  public function protectedPageTitle() {
    $config = $this->config('protected_pages.settings');
    return Html::escape($config->get('others.protected_pages_title'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('protected_pages.settings');
    $form = array();

    $form['protected_page_enter_password'] = array(
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
    );

    $form['protected_page_enter_password']['protected_page_pid'] = array(
      '#markup' => '<div class="protected_pages_description"><strong>' . $config->get('others.protected_pages_description') . '</strong></div>',
    );
    $form['protected_page_enter_password']['password'] = array(
      '#type' => 'password',
      '#title' => $config->get('others.protected_pages_password_label'),
      '#size' => 20,
      '#required' => TRUE,
    );

    $form['protected_page_pid'] = array(
      '#type' => 'hidden',
      '#value' => $this->getRequest()->query->get('protected_page'),
    );

    $form['protected_page_enter_password']['submit'] = array(
      '#type' => 'submit',
      '#value' => $config->get('others.protected_pages_submit_button_text'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('protected_pages.settings');
    $global_password_setting = $config->get('password.per_page_or_global');

    if ($global_password_setting == 'per_page_password') {
      $fields = array('password');
      $conditions = array();
      $conditions['general'][] = array(
        'field' => 'pid',
        'value' => $form_state->getValue('protected_page_pid'),
        'operator' => '=',
      );

      $password = $this->protectedPagesStorage->loadProtectedPage($fields, $conditions, TRUE);

      if (!$this->password->check($form_state->getValue('password'), $password)) {

        $form_state->setErrorByName('password', $config->get('others.protected_pages_incorrect_password_msg'));
      }
    }
    elseif ($global_password_setting == 'per_page_or_global') {
      $fields = array('password');
      $conditions = array();
      $conditions['general'][] = array(
        'field' => 'pid',
        'value' => $form_state->getValue('protected_page_pid'),
        'operator' => '=',
      );

      $password = $this->protectedPagesStorage->loadProtectedPage($fields, $conditions, TRUE);
      $global_password = $config->get('password.protected_pages_global_password');
      if (!$this->password->check($form_state->getValue('password'), $password) && !$this->password->check($form_state->getValue('password'), $global_password)) {

        $form_state->setErrorByName('password', $config->get('others.protected_pages_incorrect_password_msg'));
      }
    }
    else {
      $global_password = $config->get('password.protected_pages_global_password');

      if (!$this->password->check($form_state->getValue('password'), $global_password)) {
        $form_state->setErrorByName('password', $config->get('others.protected_pages_incorrect_password_msg'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('protected_pages.settings');
    $_SESSION['_protected_page']['passwords'][$form_state->getValue('protected_page_pid')]['request_time'] = REQUEST_TIME;
    $session_expire_time = $config->get('password.protected_pages_session_expire_time');
    if ($session_expire_time) {
      $_SESSION['_protected_page']['passwords'][$form_state->getValue('protected_page_pid')]['expire_time'] = strtotime("+{$session_expire_time} minutes");
    }
  }

}
