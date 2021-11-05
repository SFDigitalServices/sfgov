<?php

namespace Drupal\cer\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form handler for corresponding reference sync form.
 */
class CorrespondingReferenceSyncForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to synchronize the values for corresponding reference %label?', [
      '%label' => $this->entity->label()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.corresponding_reference.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Synchronize');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addStatus($this->t('Corresponding reference %label has been synchronized.', [
      '%label' => $this->entity->label()
    ]));
  }
}
