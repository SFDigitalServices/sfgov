<?php

namespace Drupal\sfgov_design_system\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display design system components page
 */
class ComponentsController extends ControllerBase {
  public function displayPage() {
    return [
      '#markup' => 'Testing',
    ];
  }
}