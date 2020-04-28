<?php

namespace Drupal\tmgmt\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a node.
 */
class JobAbortForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Abort this job?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will send a request to the translator to abort the job. After the action the job translation process will be aborted and only remaining action will be resubmitting it.');
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
    /** @var \Drupal\tmgmt\Entity\Job $entity */
    $entity = $this->entity;
    // It would make more sense to not display the button for the action,
    // however we do not know if the translator is able to abort a job until
    // we trigger the action.
    if ($entity->abortTranslation()) {
      $entity->addMessage('The user ordered aborting the Job through the UI.');
    }
    tmgmt_write_request_messages($entity);
    $form_state->setRedirectUrl($this->entity->toUrl());
  }

}
