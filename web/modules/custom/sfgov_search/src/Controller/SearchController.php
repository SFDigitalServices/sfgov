<?php

namespace Drupal\sfgov_search\Controller;

use Drupal\Core\Controller\ControllerBase;

class SearchController extends ControllerBase {
  public function content() {
    $searchKeyword = \Drupal::request()->query->get('keyword') ?: '';
    return [
      '#type' => 'markup',
      '#markup' => $this->t('<div id="sfgov_search_results"></div>'),
      '#attached' => array(
        'library' => array(
          'sfgov_search/search',
        ),
        'drupalSettings' => array(
          'sfgovSearch' => array(
            'keyword' => $searchKeyword,
          ),
        ),
      ),
    ];
  }
}