<?php

namespace Drupal\sfgov_search\Controller;

use Drupal\Core\Controller\ControllerBase;

class SearchController extends ControllerBase {
  public function content() {
    $searchKeyword = \Drupal::request()->query->get('keyword') ?: '';
    $html = '<div id="sfgov-search-results-container">' .
            '  <div id="sfgov-search-messages" class="sfgov-search-messages"></div>' . 
            '  <div id="sfgov-search-results-count"></div>' .
            '  <div id="sfgov-search-results" class="add-height"></div>' .
            '  <div id="other-sfgov-search-results"></div>' .
            '  <div class="sfgov-search-pagination"></div>' .
            '  <div class="sfgov-search-mobile-more"><a href="javascript:void(0)">' . $this->t('Show more results') . '</a></div>' .
            '  <div id="sfgov-search-overlay"></div>' .
            '  <div id="sfgov-search-loading"><div class="loader loader-default is-active"></div></div>' .
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