<?php

namespace Drupal\sfgov_design_system\Controller;

use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Controller\ControllerBase;

/**
 * Display design system components page
 */
class ComponentsController extends ControllerBase {
  const SFGOV_DRUPAL_THEME = DRUPAL_ROOT . '/themes/custom/sfgovpl';
  const SFDS_DESIGN_SYSTEM = self::SFGOV_DRUPAL_THEME . '/node_modules/sfgov-design-system';
  protected $buttonYaml;

  public function __construct() {
    $this->buttonYaml = Yaml::parse(file_get_contents(self::SFDS_DESIGN_SYSTEM . '/src/components/button/button.config.yml'));
  }

  public function displayPage() {
    return [
      '#theme' => 'sfgov_design_system_components',
      '#some_variable' => $this->t('some variable value'),
      '#sfds_components' => [
        'button' => $this->buttonYaml,
      ],
    ];
  }
}