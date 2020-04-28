<?php

namespace Drupal\tmgmt\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a node.
 */
class JobResubmitForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Resubmit as a new job?', array('%title' => $this->entity->label()));
  }

  public function getDescription() {
    return $this->t('This creates a new job with the same items which can then be submitted again. In case the sources meanwhile changed, the new job will reflect the update.');
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
    $new_job = $this->entity->cloneAsUnprocessed();
    $new_job->uid = $this->currentUser()->id();
    $new_job->save();
    /** @var \Drupal\tmgmt\JobItemInterface $item */
    foreach ($this->entity->getItems() as $item) {
      $item_to_resubmit = $item->cloneAsActive();
      $new_job->addExistingItem($item_to_resubmit);
    }

    $this->entity->addMessage('Job has been duplicated as a new job <a href=":url">#@id</a>.',
      array(':url' => $new_job->toUrl()->toString(), '@id' => $new_job->id()));
    $new_job->addMessage('This job is a duplicate of the previously aborted job <a href=":url">#@id</a>',
      array(':url' => $this->entity->toUrl()->toString(), '@id' => $this->entity->id()));

    $this->messenger()->addStatus(t('The aborted job has been duplicated. You can resubmit it now.'));
    $form_state->setRedirectUrl($new_job->toUrl());
  }

}
