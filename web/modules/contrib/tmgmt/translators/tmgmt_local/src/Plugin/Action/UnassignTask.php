<?php

namespace Drupal\tmgmt_local\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Redirects to a task assign form.
 *
 * @Action(
 *   id = "tmgmt_local_task_unassign_multiple",
 *   label = @Translation("Unassign"),
 *   type = "tmgmt_local_task",
 * )
 */
class UnassignTask extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    /** @var \Drupal\tmgmt_local\LocalTaskInterface $task */
    foreach ($entities as $task) {
      $task->unassign();
      $task->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple(array($object));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('unassign', $account, $return_as_object);
  }

}
