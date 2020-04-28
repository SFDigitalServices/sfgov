<?php

namespace Drupal\tmgmt_local\Entity\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the local task entity type.
 */
class LocalTaskViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['tmgmt_local_task']['status'] = array(
      'title' => t('Status'),
      'help' => t('Display the status of the task.'),
      'field' => array(
        'id' => 'tmgmt_local_task_status',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
    );
    $data['tmgmt_local_task']['progress'] = array(
      'title' => t('Progress'),
      'help' => t('Displays the progress of a job.'),
      'real field' => 'tltid',
      'field' => array(
        'id' => 'tmgmt_local_progress',
      ),
    );
    $data['tmgmt_local_task']['word_count'] = array(
      'title' => t('Word count'),
      'help' => t('Displays the word count of a job.'),
      'real field' => 'tltid',
      'field' => array(
        'id' => 'tmgmt_local_wordcount',
      ),
    );
    $data['tmgmt_local_task']['item_count'] = array(
      'title' => t('Item count'),
      'help' => t('Show the amount of items per task (per job item status)'),
      'real field' => 'tltid',
      'field' => array(
        'id' => 'tmgmt_local_item_count',
      ),
    );
    $data['tmgmt_job']['eligible'] = array(
      'title' => t('Eligible'),
      'help' => t('Limit translation tasks to those that the user can translate'),
      'real field' => 'tltid',
      'filter' => array(
        'id' => 'tmgmt_local_task_eligible',
      ),
    );
    // Manager handlers.
    $data['tmgmt_job']['task'] = array(
      'title' => t('Translation task'),
      'help' => t('Get the translation task of the job'),
      'relationship' => array(
        'base' => 'tmgmt_local_task',
        'base field' => 'tjid',
        'real field' => 'tjid',
        'label' => t('Job'),
      ),
    );

    $data['tmgmt_local_task']['footer'] = array(
      'title' => t('Task Overview legend'),
      'help' => t('Add task state legends'),
      'area' => array(
        'id' => 'tmgmt_local_task_legend',
        ),
      );
    return $data;
  }

}
