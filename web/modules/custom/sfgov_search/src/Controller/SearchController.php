<?php

namespace Drupal\sfgov_search\Controller;

use Drupal\Core\Controller\ControllerBase;

class SearchController extends ControllerBase {
  public function content() {
    $searchKeyword = \Drupal::request()->query->get('keyword') ?: '';
    $html = '<div id="sfgov-search-results-container">' .
            '  <div id="sfgov-search-messages" class="sfgov-search-result views-row sfgov-search-messages"></div>' . 
            '  <div id="sfgov-search-results-count" class="sfgov-search-result views-row"></div>' .
            '  <div id="sfgov-search-results"></div>' .
            '  <div id="other-sfgov-search-results"></div>' .
            '  <div class="sfgov-search-pagination"></div>' .
            '</div>';
    return [
      '#type' => 'markup',
      '#markup' => $this->t($html),
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