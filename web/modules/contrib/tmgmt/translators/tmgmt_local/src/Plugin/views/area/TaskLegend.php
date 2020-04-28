<?php

namespace Drupal\tmgmt_local\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Views area task legend handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("tmgmt_local_task_legend")
 */
class TaskLegend extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {

    $form['footer'] = tmgmt_color_legend_local_task();
    $form['footer']['#weight'] = 100;
    return $form;
  }

}
