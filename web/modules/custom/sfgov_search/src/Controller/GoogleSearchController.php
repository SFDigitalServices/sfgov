<?php

namespace Drupal\sfgov_search\Controller;

use Drupal\Core\Controller\ControllerBase;

class GoogleSearchController extends ControllerBase {

  public function content() {

    $searchKeyword = \Drupal::request()->query->get('q') ?: '';

    $build['script'] = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => [
        'async' => 'async',
        'src' => "https://cse.google.com/cse.js?cx=c785e24afcb1a4da8&key=AIzaSyC2EwXHN65Wgo2SQwNApPYZzUV52TPzjCs",
      ]
    ];

    $form = \Drupal::formBuilder()->getForm('Drupal\sfgov_search\Form\GoogleSearchForm');
    $build['markup'] = [
      '#theme' => 'google_search',
      '#title' => 'Google Search',
      '#form' => $form,
      '#attached' => [
        'library' => [
          'sfgov_search/google_search',
        ],
      ],
    ];

    return $build;
  }
}
