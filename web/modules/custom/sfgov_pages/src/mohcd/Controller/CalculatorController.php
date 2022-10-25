<?php

namespace Drupal\sfgov_pages\mohcd\Controller;

use Drupal\Core\Controller\ControllerBase;

class CalculatorController extends ControllerBase {
  public function content() {
    return [
      '#markup' => 'calculator',
    ];
  } 
}
