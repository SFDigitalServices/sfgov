<?php

namespace Drupal\sfgov_vaccine\Services;

use Drupal\Core\State\State;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vaccination site data.
 */
class VaxValues {
  use StringTranslationTrait;

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
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Class constructor.
   */
  public function __construct(State $state, ConfigFactory $configFactory, TranslationInterface $stringTranslation) {
    $this->state = $state;
    $this->configFactory = $configFactory;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Create the class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory'),
      $container->get('string_translation')
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
    return isset($alert_db)
      ? $alert_db['value']
      : $this->settings('template_strings.page.alert.value');
  }

  /**
   * Save Alert value as a Drupal state.
   */
  public function setAlert($value) {
    $this->state->set('vaccine_alert', $value);
  }

  /**
   * Get the header description.
   */
  public function getHeaderDescription() {
    $header_db = $this->state->get('header_description');
    return isset($header_db)
      ? $header_db['value']
      : $this->settings('template_strings.page.description');
  }

  /**
   * Set the header description.
   */
  public function setHeaderDescription($value) {
    $this->state->set('header_description', $value);
  }

}
