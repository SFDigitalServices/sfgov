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
   */
  private function getQLessData() {
    return [
      "status" => "success",
      "data" => [
        "timestamp" => "2021-05-04T00:03:18.089Z",
        "queues" => [
          [
            "id" => 2879556,
            "name" => "Some queue",
            "state" => "ACTIVE",
            "location_id" => 3294872,
            "wait_time" => 60,
          ],
          [
            "id" => 2897234,
            "name" => "Some other queue",
            "state" => "CLOSED",
            "location_id" => 3294872,
            "wait_time" => NULL,
          ],
          [
            "id" => 2895557234,
            "name" => "Some other third queue",
            "state" => "ACTIVE",
            "location_id" => 3294872,
            "wait_time" => 85,
          ],

          [
            "id" => 2895557234,
            "name" => "Fourth queue",
            "state" => "ACTIVE",
            "location_id" => 3294872,
            "wait_time" => 15,
          ],

          [
            "id" => 2895557234,
            "name" => "Inactive queue",
            "state" => "INACTIVE",
            "location_id" => 3294872,
            "wait_time" => 15,
          ],

          [
            "id" => 2895557234,
            "name" => "Closing queue",
            "state" => "CLOSING",
            "location_id" => 3294872,
            "wait_time" => 15,
          ],
        ],
      ],
    ];
  }

  /**
   * Get config settings for this module.
   *
   * $value Int|NUll.
   */
  private function displayWaitTime($value, $state) {

    $open = '';
    $text = '';

    if ($value == NULL) {
      $state = 'CLOSED';
    }

    else {

      $hours = floor($value / 60);
      $minutes = $value % 60;

      $min_text = '';

      if ($minutes == 1) {
        $min_text = sprintf('%s minute', $minutes);
      }
      elseif ($minutes > 1) {
        $min_text = sprintf('%s minutes', $minutes);
      }

      $hour_text = '';

      if ($hours == 1) {
        $hour_text = sprintf('%s hour', $hours);
      }
      elseif ($hours > 1) {
        $hour_text = sprintf('%s hours', $hours);
      }

      $text = sprintf('%s %s', $hour_text, $min_text);
    }

    switch ($state) :
      case 'ACTIVE':
        $open = TRUE;
        break;

      case 'INACTIVE':
      case 'CLOSED':
        $open = FALSE;
        $text = t('Closed');
        break;

      case 'CLOSING':
        $open = FALSE;
        $text = t('Full');
        break;
    endswitch;

    return [
      'data' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $text,
        '#attributes' => ['class' => $open ? 'qless-open' : 'qless-closed'],
      ],
    ];
  }

  /**
   *
   */
  private function buildRow($title, $value, $state) {

    return [$title, $this->displayWaitTime($value, $state)];
  }

  /**
   * Render the Queue as a Table.
   */
  public function renderTable() {

    $title = t($this->settings('title'));
    $caption = t($this->settings('caption'));
    $thead1 = t($this->settings('thead1'));
    $thead2 = t($this->settings('thead2'));
    $header = [
      $thead1,
      $thead2,
    ];

    $data = $this->getQLessData();
    $queues = $data['data']['queues'];
    $rows = [];

    foreach ($queues as $id => $queue) {
      array_push($rows, $this->buildRow($queue['name'], $queue['wait_time'], $queue['state']));
    }

    $footer = [
      ['', $data['timestamp']],
    ];

    return [
      '#type' => 'table',
      '#prefix' => '<h2>' . $title . '</h2>',
      '#attributes' => ['class' => 'sfgov-table'],
      '#responsive' => FALSE,
      '#caption' => $caption,
      '#header' => $header,
      '#rows' => $rows,
      '#footer' => $footer,
    ];
  }

}
