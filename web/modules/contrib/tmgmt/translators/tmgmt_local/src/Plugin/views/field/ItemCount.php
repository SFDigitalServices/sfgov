<?php

namespace Drupal\tmgmt_local\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Views;

/**
 * Field handler to show the amount of job items per task.
 *
 * @ViewsField("tmgmt_local_item_count")
 */
class ItemCount extends FieldPluginBase {

  /**
   * Where the $query object will reside.
   *
   * @var \Drupal\views\Plugin\views\query\Sql
   */
  public $query = NULL;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['state'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = array('' => t('- All -'));
    $options += JobItem::getStates();
    $form['state'] = array(
      '#title' => t('Job item state'),
      '#description' => t('Count only job items of a certain state.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->options['state'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // Therefore construct the join.
    // Add the join for the tmgmt_job_item table.
    $configuration = array(
      'table' => 'tmgmt_job_item',
      'field' => 'tjid',
      'left_table' => $this->tableAlias,
      'left_field' => 'tjid',
      'operator' => '=',
    );
    if (!empty($this->options['state'])) {
      $configuration['extra'] = [
        [
          'field' => 'state',
          'value' => $this->options['state'],
        ]
      ];
    }
    /** @var \Drupal\views\Plugin\views\join\Standard $join */
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);

    // Add the join to the tmgmt_job_item table.
    $this->tableAlias = $this->query->addTable('tmgmt_job_item', $this->relationship, $join);

    // And finally add the count of the job items field.
    $params = array('function' => 'count');
    $this->field_alias = $this->query->addField($this->tableAlias, 'tjiid', NULL, $params);

    $this->addAdditionalFields();
  }

}
