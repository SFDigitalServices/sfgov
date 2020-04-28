<?php

namespace Drupal\tmgmt\Plugin\views\filter;

use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\JobInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter based on job state.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("tmgmt_job_state_filter")
 */
class JobState extends ManyToOne {

  /**
   * Where the $query object will reside.
   *
   * @var \Drupal\views\Plugin\views\query\Sql
   */
  public $query = NULL;

  /**
   * Gets the values of the options.
   *
   * @return array
   *   Returns options.
   */
  public function getValueOptions() {
    $this->valueOptions = [
      'open_jobs' => t('- Open jobs -'),
      '0' => t('Unprocessed'),
    ];

    $state_definitions = JobItem::getStateDefinitions();
    foreach ($state_definitions as $state => $state_definition) {
      if (!empty($state_definition['show_job_filter'])) {
        $this->valueOptions['job_item_' . $state] = $this->t('Items - @item_state', ['@item_state' => $state_definition['label']]);
      }
    }

    $this->valueOptions += [
      '2' => t('Rejected'),
      '4' => t('Aborted'),
      '5' => t('Finished'),
    ];
    if (\Drupal::service('tmgmt.continuous')->checkIfContinuousTranslatorAvailable()) {
      $this->valueOptions['6'] = t('Continuous');
    }
    return $this->valueOptions;
  }

  /**
   * Set the operators.
   *
   * @return array
   *   Returns operators.
   */
  function operators() {
    $operators = array(
      'job_state' => array(
        'title' => $this->t('Job State'),
        'short' => $this->t('job state'),
        'values' => 1,
      )
    );
    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $state = reset($this->value);
    $field = $this->field;
    $table = $this->table;

    if (strpos($state, 'job_item_') !== FALSE) {
      $job_item_state = str_replace('job_item_', '', $state);

      $table_alias = 'job_item';
      $job_item_field = 'state';
      $state_definitions = JobItem::getStateDefinitions();
      if ($state_definitions[$job_item_state]['type'] == 'translator_state') {
        $job_item_field = 'translator_state';
      }

      // Create a sub query to add the state of job item to the view.
      $sub_query = \Drupal::database()->select('tmgmt_job_item', $table_alias);
      $sub_query->addField($table_alias, 'tjid');
      $sub_query->condition("$table_alias.$job_item_field", $job_item_state, '=');

      // Select all job items that are not in the sub query.
      $this->query->addWhere($this->options['group'], 'tjid', $sub_query, 'IN');
      $this->query->addWhere($this->options['group'], "$table.$field", JobInterface::STATE_ACTIVE, 'IN');
    }
    else {
      $operator = '=';
      if ($state == 'open_jobs') {
        $state = [
          JobInterface::STATE_UNPROCESSED,
          JobInterface::STATE_ACTIVE,
          JobInterface::STATE_REJECTED,
          JobInterface::STATE_CONTINUOUS,
        ];
        $operator = 'IN';
      }
      $this->query->addWhere($this->options['group'], "$table.$field", $state, $operator);
    }

  }

}
