<?php

namespace Drupal\tmgmt_local\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\views\Views;

/**
 * Assign task confirmation form.
 */
class LocalTaskAssignForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    parent::buildForm($form, $form_state);

    $roles = tmgmt_local_translator_roles();
    if (empty($roles)) {
      $this->messenger()->addWarning(t('No user role has the "provide translation services" permission. <a href="@url">Configure permissions</a> for the Drupal user module.',
        array('@url' => URL::fromRoute('user.admin_permissions'))));
    }

    $form['tuid'] = array(
      '#title' => t('Assign to'),
      '#type' => 'select',
      '#empty_option' => t('- Select user -'),
      '#options' => tmgmt_local_get_assignees_for_tasks([$this->getEntity()->id()]),
      '#required' => TRUE,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Assign tasks'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\user\Entity\User $assignee */
    $assignee = User::load($form_state->getValue('tuid'));

    /** @var \Drupal\tmgmt_local\LocalTaskInterface $task */
    $task = $this->getEntity();
    $task->assign($assignee);
    $task->save();

    $this->messenger()->addStatus(t('Assigned @label to user @assignee_name.', array('@label' => $task->label(), '@assignee_name' => $assignee->getAccountName())));

    $view = Views::getView('tmgmt_local_task_overview');
    $view->initDisplay();
    $form_state->setRedirect($view->getUrl()->getRouteName());
  }

}
