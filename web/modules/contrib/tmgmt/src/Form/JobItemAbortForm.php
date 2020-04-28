<?php

namespace Drupal\tmgmt\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\TMGMTException;

/**
 * Provides a form for deleting a node.
 */
class JobItemAbortForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to abort the job item %title?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Aborted job items can no longer be accepted. The provider will not be notified.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\Entity\JobItem $entity */
    $entity = $this->entity;
    try {
      if (!$entity->abortTranslation()) {
        tmgmt_write_request_messages($entity);
      }
    }
    catch(TMGMTException $e) {
      $this->messenger()->addError(t('Job item cannot be aborted: %error.', array(
        '%error' => $e->getMessage(),
      )));
    }
    $form_state->setRedirectUrl($this->entity->toUrl());
  }

}
