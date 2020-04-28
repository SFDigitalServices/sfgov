<?php

/**
 * @file
 * Contains \Drupal\protected_pages\Form\ProtectedPagesDeleteConfirmForm.
 */

namespace Drupal\protected_pages\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\protected_pages\ProtectedPagesStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides delete protected page confirm form.
 */
class ProtectedPagesDeleteConfirmForm extends ConfirmFormBase {

  /**
   * The protected page id.
   *
   * @var int
   */
  protected $pid;

  /**
   * The protected pages storage service.
   *
   * @var \Drupal\protected_pages\ProtectedPagesStorage
   */
  protected $protectedPagesStorage;

  /**
   * Constructs a ProtectedPagesController object.
   *
   * @param \Drupal\protected_pages\ProtectedPagesStorage $protectedPagesStorage
   *   The protected pages storage service.
   */
  public function __construct(ProtectedPagesStorage $protectedPagesStorage) {
    $this->protectedPagesStorage = $protectedPagesStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('protected_pages.storage')
    );
  }

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this page?');
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return new Url('protected_pages_list');
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'protected_pages_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete page');
  }

  /**
   * {@inheritdoc}
   *
   * @param int $pid
   *   (optional) The ID of the protected page to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pid = NULL) {
    $this->pid = $pid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->protectedPagesStorage->deleteProtectedPage($this->pid);
    drupal_set_message($this->t('The protected page has been successfully deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
