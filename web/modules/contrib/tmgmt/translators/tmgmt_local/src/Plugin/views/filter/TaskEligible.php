<?php

namespace Drupal\tmgmt_local\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Field handler which shows the link for assign translation task to user.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("tmgmt_local_task_eligible")
 */
class TaskEligible extends FilterPluginBase {

  /**
   * Where the $query object will reside.
   *
   * @var \Drupal\views\Plugin\views\query\Sql
   */
  public $query = NULL;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $source = $this->tableAlias . '.source_language';
    $target = $this->tableAlias . '.target_language';
    // Add a new group for the language abilities, which are a set of source
    // and target language combinations.
    $this->query->setWhereGroup('OR', 'eligible');
    // Return all language abilities for the current user.
    foreach (tmgmt_local_supported_language_pairs(NULL, array(\Drupal::currentUser()->id())) as $key => $ability) {
      $key = str_replace('-', '_', $key);
      $arguments = array(':source_' . $key => $ability['source_language'], ':target_' . $key => $ability['target_language']);
      $this->query->addWhereExpression('eligible', "$source = :source_$key AND $target = :target_$key", $arguments);
    }
  }

}
