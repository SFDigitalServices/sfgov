<?php

namespace Drupal\tmgmt\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Views area job item legend handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("tmgmt_job_item_legend")
 */
class JobItemLegend extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {

    $form['footer'] = tmgmt_color_job_item_legend();
    $form['footer']['#weight'] = 100;
    return $form;
  }

}
