<?php

namespace Drupal\tmgmt_local\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\tmgmt_local\Entity\LocalTask;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a node deletion confirmation form.
 */
class AssignMultiple extends FormBase {

  /**
   * The array of tasks to assign.
   *
   * @var string[]
   */
  protected $taskInfo = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'task_multiple_assign';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->taskInfo = $this->tempStoreFactory->get('task_multiple_assign')->get(\Drupal::currentUser()->id());
    $form_state->set('tasks', array_keys($this->taskInfo));

    $roles = tmgmt_local_translator_roles();
    if (empty($roles)) {
      $this->messenger()->addWarning(t('No user role has the "provide translation services" permission. <a href="@url">Configure permissions</a> for the Drupal user module.',
        array('@url' => URL::fromRoute('user.admin_permissions'))));
    }

    $form['tuid'] = array(
      '#title' => t('Assign to'),
      '#type' => 'select',
      '#empty_option' => t('- Select user -'),
      '#options' => tmgmt_local_get_assignees_for_tasks($form_state->get('tasks')),
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
    /** @var User $assignee */
    $assignee = User::load($form_state->getValue('tuid'));

    $how_many = 0;
    foreach ($form_state->get('tasks') as $task_id) {
      $task = LocalTask::load($task_id);
      if ($task) {
        $task->assign($assignee);
        $task->save();
        ++$how_many;
      }
    }

    $this->messenger()->addStatus(t('Assigned @how_many to user @assignee_name.', array('@how_many' => $how_many, '@assignee_name' => $assignee->getAccountName())));

    $view = Views::getView('tmgmt_local_task_overview');
    $view->initDisplay();
    $form_state->setRedirect($view->getUrl()->getRouteName());
  }

}
