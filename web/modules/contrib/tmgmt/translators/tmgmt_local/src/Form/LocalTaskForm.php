<?php

namespace Drupal\tmgmt_local\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt_local\Entity\LocalTask;
use Drupal\tmgmt_local\LocalTaskInterface;
use Drupal\user\Entity\User;
use Drupal\views\Views;

/**
 * Form controller for the localTask edit forms.
 *
 * @ingroup tmgmt_local_task
 */
class LocalTaskForm extends ContentEntityForm {

  /**
   * The local task.
   *
   * @var \Drupal\tmgmt_local\LocalTaskInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var LocalTask $local_task */
    $local_task = $this->entity;

    $states = LocalTask::getStatuses();
    // Set the title of the page to the label and the current state of the
    // localTask.
    $form['#title'] = (t('@title (@source to @target, @state)', array(
      '@title' => $local_task->label(),
      '@source' => $local_task->getJob()->getSourceLanguage()->getName(),
      '@target' => $local_task->getJob()->getTargetLanguage()->getName(),
      '@state' => $states[$local_task->getStatus()],
    )));

    $assignees = tmgmt_local_assignees($local_task->getJob()->getSourceLangcode(), array($local_task->getJob()->getTargetLangcode()));
    $form['tuid'] = array(
      '#title' => t('Assigned'),
      '#type' => 'select',
      '#options' => $assignees,
      '#empty_option' => t('- Unassigned -'),
      '#default_value' => $local_task->getAssignee() ? $local_task->getAssignee()->id() : NULL,
      '#access' => \Drupal::currentUser()->hasPermission('administer tmgmt') || \Drupal::currentUser()->hasPermission('administer translation tasks'),
    );

    $form['info'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('tmgmt-local-task-info', 'clearfix')),
      '#weight' => 0,
      '#tree' => TRUE,
    );

    // Check for label value and set for dynamically change.
    if ($form_state->getValue('label') && $form_state->getValue('label') == $local_task->label()) {
      $form_state->setValue('label', $local_task->label());
    }

    $form['title']['widget'][0]['value']['#description'] = t('You can provide a label for this localTask in order to identify it easily later on. Or leave it empty to use the default one.');
    $form['title']['#group'] = 'info';
    $form['title']['#prefix'] = '<div id="tmgmt-ui-label">';
    $form['title']['#suffix'] = '</div>';

    $form['info']['source_language'] = array(
      '#title' => t('Source language'),
      '#type' => 'item',
      '#markup' => $local_task->getJob()->getSourceLanguage()->getName(),
      '#prefix' => '<div id="tmgmt-ui-source-language" class="tmgmt-ui-source-language tmgmt-ui-info-item">',
      '#suffix' => '</div>',
      '#value' => $local_task->getJob()->getSourceLangcode(),
    );

    $form['info']['target_language'] = array(
      '#title' => t('Target language'),
      '#type' => 'item',
      '#markup' => $local_task->getJob()->getTargetLanguage()->getName(),
      '#prefix' => '<div id="tmgmt-ui-target-language" class="tmgmt-ui-target-language tmgmt-ui-info-item">',
      '#suffix' => '</div>',
      '#value' => $local_task->getJob()->getTargetLangcode(),
    );

    $form['info']['word_count'] = array(
      '#type' => 'item',
      '#title' => t('Total word count'),
      '#markup' => number_format($local_task->getWordCount()),
      '#prefix' => '<div class="tmgmt-ui-word-count tmgmt-ui-info-item">',
      '#suffix' => '</div>',
    );

    // Display created time only for localTasks that are not new anymore.
    if (!$local_task->getJob()->isUnprocessed()) {
      $form['info']['created'] = array(
        '#type' => 'item',
        '#title' => t('Created'),
        '#markup' => \Drupal::service('date.formatter')->format($local_task->getJob()->getCreatedTime()),
        '#prefix' => '<div class="tmgmt-ui-created tmgmt-ui-info-item">',
        '#suffix' => '</div>',
        '#value' => $local_task->getJob()->getCreatedTime(),
      );
    }

    $form['info']['status'] = array(
      '#type' => 'item',
      '#title' => t('Status'),
      '#markup' => $states[$local_task->getStatus()],
      '#prefix' => '<div class="tmgmt-local-ui-status tmgmt-ui-info-item">',
      '#suffix' => '</div>',
    );

    if ($view = Views::getView('tmgmt_local_task_items')) {
      $block = $view->preview('block_1', [$local_task->id()]);
      $form['items'] = array(
        '#type' => 'item',
        '#title' => $view->getTitle(),
        '#prefix' => '<div class="tmgmt-local-task-items">',
        '#markup' => \Drupal::service('renderer')->render($block),
        '#attributes' => array('class' => array('tmgmt-local-task-items')),
        '#suffix' => '</div>',
        '#weight' => 10,
      );
    }
    $form['footer'] = tmgmt_color_legend_local_task_item();
    $form['footer']['#weight'] = 100;
    $form['#attached']['library'][] = 'tmgmt/admin';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = $this->t('Save task');
    $actions['submit']['#access'] = \Drupal::currentUser()->hasPermission('administer tmgmt') || \Drupal::currentUser()->hasPermission('administer translation tasks');

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt_local\Entity\LocalTask $task */
    $task = $this->getEntity();

    if (!empty($form_state->getValue('tuid'))) {
      /** @var User $assignee */
      $assignee = User::load($form_state->getValue('tuid'));
      $task->assign($assignee);

      $this->messenger()->addStatus(t('Assigned to user @assignee_name.', ['@assignee_name' => $assignee->getAccountName()]));
    }
    else {
      $task->setStatus(LocalTaskInterface::STATUS_UNASSIGNED);

      $this->messenger()->addStatus(t('Unassigned from translation local task @label.', array('@label' => $task->label())));
    }
    $this->entity->save();

    $view = Views::getView('tmgmt_local_task_overview');
    $view->initDisplay();
    $form_state->setRedirect($view->getUrl()->getRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->entity->toUrl('delete-form'));
  }

}
