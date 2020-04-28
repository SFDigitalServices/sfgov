<?php

namespace Drupal\tmgmt\Entity\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the job entity type.
 */
class JobItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['tmgmt_job_item']['label'] = array(
      'title' => 'Label',
      'help' => 'Displays a label of the job item.',
      'real field' => 'tjiid',
      'field' => array(
        'id' => 'tmgmt_entity_label',
      ),
    );
    $data['tmgmt_job_item']['progress'] = array(
      'title' => 'Progress',
      'help' => 'Displays the progress of a job item.',
      'real field' => 'tjiid',
      'field' => array(
        'id' => 'tmgmt_progress',
      ),
    );
    $data['tmgmt_job_item']['type'] = array(
      'title' => t('Type'),
      'help' => t('Displays a type of the job item.'),
      'real field' => 'tjiid',
      'field' => array(
        'id' => 'tmgmt_job_item_type',
      ),
    );

    $data['tmgmt_job_item']['state'] = array(
      'title' => 'State',
      'help' => 'Displays the state of the job item.',
      'field' => array(
        'id' => 'tmgmt_job_item_state',
      ),
      'filter' => array(
        'id' => 'tmgmt_job_item_state_filter',
      ),
    );

    $data['tmgmt_job_item']['footer'] = array(
      'title' => t('Job Item Overview legend'),
      'help' => t('Add job item state legends'),
      'area' => array(
        'id' => 'tmgmt_job_item_legend',
        ),
      );
    return $data;
  }
}
