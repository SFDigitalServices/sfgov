<?php

namespace Drupal\sfgov_vaccine\Services;

use Drupal\Core\State\State;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class VaxValues {
  /**
   * State object.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Class constructor.
   */
  public function __construct(State $state, ConfigFactory $configFactory) {
    $this->state = $state;
    $this->configFactory = $configFactory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory')
    );
  }

  /**
   * Get config settings for this module.
   */
  public function settings($value) {
    return $this->configFactory->get('sfgov_vaccine.settings')->get($value);
  }

  /**
   * In order to avoid overriding value, use Drupal state if it exists.
   */
  public function getAlert() {
    $alert_db = $this->state->get('vaccine_alert');
    $alert_db_val = $alert_db['value'];
    $alert_config_val = $this->settings('template_strings.page.alert.value');
    return isset($alert_db) ? $alert_db_val : $alert_config_val;
  }

  /**
   * Save Alert value as a Drupal state.
   */
  public function setAlert($value) {
    $this->state->set('vaccine_alert', $value);
  }

  public function getHeaderDescription() {
    $header_db = $this->state->get('header_description');
    $header_db_val = $header_db['value'];
    $header_config_val = $this->settings('template_strings.page.description');
    $headerDescription = isset($header_db) ? $header_db_val : $header_config_val;
    return t($headerDescription);
  }

  public function setHeaderDescription($value) {
    $this->state->set('header_description', $value);
  }
}

