<?php

/**
 * @file
 * Contains \Drupal\protected_pages\Form\ProtectedPagesEditForm.
 */

namespace Drupal\protected_pages\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\protected_pages\ProtectedPagesStorage;
use Drupal\Component\Utility\Html;
use Drupal\Core\Password\PasswordInterface;

/**
 * Provides an edit protected page form.
 */
class ProtectedPagesEditForm extends FormBase {

  /**
   * The protected pages storage service.
   *
   * @var \Drupal\protected_pages\ProtectedPagesStorage
   */
  protected $protectedPagesStorage;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Provides the password hashing service object.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $password;

  /**
   * Constructs a new ProtectedPagesEditForm.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password hashing service.
   */
  public function __construct(PathValidatorInterface $path_validator, PasswordInterface $password, ProtectedPagesStorage $protectedPagesStorage) {

    $this->pathValidator = $path_validator;
    $this->password = $password;
    $this->protectedPagesStorage = $protectedPagesStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('path.validator'), $container->get('password'), $container->get('protected_pages.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'protected_pages_edit_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param int $pid
   *   The ID of the protected page.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pid = NULL) {
    $fields = array('path');
    $conditions = array();
    $conditions['general'][] = array(
      'field' => 'pid',
      'value' => $pid,
      'operator' => '=',
    );

    $path = $this->protectedPagesStorage->loadProtectedPage($fields, $conditions, TRUE);
    $form = array();

    $form['rules_list'] = array(
      '#title' => $this->t("Edit Protected Page Relative path and password."),
      '#type' => 'details',
      '#description' => $this->t('Please enter the relative path and its corresponding
    password. When user opens this url, they will asked to enter password to
    view this page. For example, "/node/5", "/new-events" etc.'),
      '#open' => TRUE,
    );
    $form['rules_list']['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Relative Path'),
      '#default_value' => $path,
      '#description' => $this->t('Enter relative drupal path. For example, "/node/5", "/new-events" etc.'),
      '#required' => TRUE,
    );
    $form['rules_list']['password'] = array(
      '#type' => 'password_confirm',
      '#size' => 25,
    );
    $form['rules_list']['pid'] = array(
      '#type' => 'hidden',
      '#value' => $pid,
    );
    $form['rules_list']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entered_path = rtrim(trim($form_state->getValue('path')), " \\/");

    if (substr($entered_path, 0, 1) != '/') {
      $form_state->setErrorByName('path', $this->t('The path needs to start with a slash.'));
    }
    else {
      $normal_path = \Drupal::service('path.alias_manager')
          ->getPathByAlias($form_state->getValue('path'));
      $path_alias = Unicode::strtolower(\Drupal::service('path.alias_manager')
                  ->getAliasByPath($form_state->getValue('path')));
      if (!$this->pathValidator->isValid($normal_path)) {
        $form_state->setErrorByName('path', $this->t('Please enter a correct path!'));
      }
      $fields = array('pid');
      $conditions = array();
      $conditions['or'][] = array(
        'field' => 'path',
        'value' => $normal_path,
        'operator' => '=',
      );
      $conditions['or'][] = array(
        'field' => 'path',
        'value' => $path_alias,
        'operator' => '=',
      );
      $conditions['and'][] = array(
        'field' => 'pid',
        'value' => $form_state->getValue('pid'),
        'operator' => '<>',
      );

      $pid = $this->protectedPagesStorage->loadProtectedPage($fields, $conditions, TRUE);
      if ($pid) {
        $form_state->setErrorByName('path', $this->t('Duplicate path entry is not allowed. There is already a path or its alias exists.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page_data = array();
    $password = $form_state->getValue('password');
    if (!empty($password)) {
      $page_data['password'] = $this->password->hash(Html::escape($password));
    }
    $page_data['path'] = Html::escape($form_state->getValue('path'));

    $this->protectedPagesStorage->updateProtectedPage($page_data, $form_state->getValue('pid'));
    drupal_set_message($this->t('The protected page settings has been successfully saved.'));
    drupal_flush_all_caches();
    $form_state->setRedirect('protected_pages_list');
  }

}
