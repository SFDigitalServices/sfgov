<?php

namespace Drupal\sfgov_design_system\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display design system components page
 */
class ComponentsController extends ControllerBase {
  public function displayPage() {
    return [
      '#theme' => 'sfgov_design_system_components',
      '#some_variable' => $this->t('some variable value'),
    ];
  }
}