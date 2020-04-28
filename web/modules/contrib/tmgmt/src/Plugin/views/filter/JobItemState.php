<?php

namespace Drupal\tmgmt\Plugin\views\filter;

use Drupal\tmgmt\Entity\JobItem;
use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter based on job item state.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("tmgmt_job_item_state_filter")
 */
class JobItemState extends ManyToOne {

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
      'open_job_items' => t('- Open jobs items -'),
    ];

    $state_definitions = JobItem::getStateDefinitions();
    foreach ($state_definitions as $state => $state_definition) {
      $this->valueOptions[$state] = $state_definition['label'];
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
    $operators = [
      'job_item_state' => [
        'title' => $this->t('Job State'),
        'short' => $this->t('job state'),
        'values' => 1,
      ],
    ];
    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $key = reset($this->value);
    $field = $this->field;
    $table = $this->table;
    switch ($key) {
      case 'open_job_items':
        $this->query->addWhere($this->options['group'], "$table.$field", [0, 1, 2], 'IN');
        break;
      default:

        $state_definitions = JobItem::getStateDefinitions();
        if ($state_definitions[$key]['type'] == 'translator_state') {
          $field = 'translator_state';
        }

        $this->query->addWhere($this->options['group'], "$table.$field", $key, '=');
        break;
    }
  }

}
