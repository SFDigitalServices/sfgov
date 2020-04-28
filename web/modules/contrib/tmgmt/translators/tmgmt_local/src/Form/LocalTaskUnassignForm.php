<?php

namespace Drupal\tmgmt_local\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tmgmt_local\Entity\LocalTask;
use Drupal\views\Views;

/**
 * Unassign task confirmation form.
 */
class LocalTaskUnassignForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return new TranslatableMarkup('Are you sure you want to unassign from the translation task @label?', ['@label' => $this->getEntity()->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $view = Views::getView('tmgmt_local_task_overview');
    $view->initDisplay();
    return $view->getUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Unassign');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var LocalTask $entity */
    $entity = $this->getEntity();

    $entity->unassign();
    $entity->save();

    $this->messenger()->addStatus(t('Unassigned from translation local task @label.', array('@label' => $entity->label())));

    $view = Views::getView('tmgmt_local_task_overview');
    $view->initDisplay();
    $form_state->setRedirect($view->getUrl()->getRouteName());
  }

}
