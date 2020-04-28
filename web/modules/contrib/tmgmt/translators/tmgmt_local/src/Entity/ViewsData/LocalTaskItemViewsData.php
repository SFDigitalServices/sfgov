<?php

namespace Drupal\tmgmt_local\Entity\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the local task item entity type.
 */
class LocalTaskItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['tmgmt_local_task_item']['status'] = array(
      'title' => t('Status'),
      'help' => t('Display the status of the task item.'),
      'field' => array(
        'id' => 'tmgmt_local_task_item_status',
      ),
    );
    $data['tmgmt_local_task_item']['progress'] = array(
      'title' => t('Progress'),
      'help' => t('Displays the progress of a task item.'),
      'real field' => 'tltiid',
      'field' => array(
        'id' => 'tmgmt_local_progress',
      ),
    );
    $data['tmgmt_local_task_item']['word_count'] = array(
      'title' => t('Words'),
      'help' => t('Displays the word count of a task item.'),
      'real field' => 'tltiid',
      'field' => array(
        'id' => 'tmgmt_local_wordcount',
      ),
    );
    return $data;
  }

}
