<?php

namespace Drupal\sfgov_qless\Services;

use Drupal\Core\State\State;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class QLess {
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

  /**
   *
   */
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
    return $this->configFactory->get('sfgov_qless.settings')->get($value);
  }

  /**
   * Get config settings for this module.
   *
   * $value Int|NUll
   */
  private function displayWaitTime( $value) {
    return;
  }

  /**
   * Render the Queue as a Table.
   */
  public function renderTable() {

    $caption = t($this->settings('caption'));
    $title = t($this->settings('title'));
    $thead1 = t($this->settings('thead1'));
    $thead2 = t($this->settings('thead2'));

    $header = [
      $thead1,
      $thead2
    ];

    $rows = [
      ['Buidling: Architectural Review', '15 min'],
      ['ODI: Architectural Review', '45 min'],
    ];

    $footer = [
      ['', 'time']
    ];

    return [
      '#type' => 'table',
      '#prefix' => '<h2>' . $title . '</h2>',
      '#attributes' => ['class' => 'sfgov-table'],
      '#responsive' => FALSE,
      '#caption' => $caption,
      '#header' => $header,
      '#rows' => $rows,
      '#footer' => $footer
    ];
  }
}
