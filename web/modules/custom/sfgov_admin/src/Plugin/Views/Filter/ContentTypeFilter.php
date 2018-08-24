<?php

namespace Drupal\sfgov_admin\Plugin\Views\Filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

use Drupal\sfgov_utilities\Utility;
use Drupal\views\Views;


/**
 * Filters by given list of node title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("sfgov_admin_node_titles")
 */
class ContentTypeFilter extends InOperator {
  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Allowed departments');
    $this->definition['options callback'] = array($this, 'generateOptions');
  }
  
  /**
   * Override the query so that no filtering takes place if the user doesn't
   * select any options.
   */
  public function query() {
    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * Skip validation if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * Helper function that generates the options.
   * @return array
   */
  public function generateOptions() {
    // Array keys are used to compare with the table field values.
    $content_type = null;
    if(!empty($this->definition['argument']) && !empty($this->definition['argument']['content_type'])) {
      $content_type = $this->definition['argument']['content_type'];
    }
    
    $nodes = Utility::getNodesOfContentType($content_type);
    $array = [];
    foreach($nodes as $node) {
      $array[$node->id()] = $node->getTitle();
    }
    return $array;
  }
}