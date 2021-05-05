<?php

namespace Drupal\sfgov_qless\Services;

use Drupal\Core\State\State;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get and Display QLess data.
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
   * Create QLess object.
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
  private function getQLessJson() {
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
   * Get the 'X hours X minutes' string.
   */
  private function getHoursMinutes($value, $label, $label_plural) {
    $text = '';
    if ($value > 0) {
      $label = t($label)->render();
      $label_plural = t($label_plural)->render();
      $text = sprintf('%s %s', $value, ($value == 1) ? $label : $label_plural);
    }
    return $text;
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
      $hour_text = $this->getHoursMinutes($hours, 'hour', 'hours');
      $min_text = $this->getHoursMinutes($minutes, 'minute', 'minutes');
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
   * Render Row.
   */
  private function buildRow($title, $value, $state) {
    return [$title, $this->displayWaitTime($value, $state)];
  }

  /**
   * Render the Queue as a Table.
   */
  public function renderTable() {

    // Get settings from Config.
    $title = t($this->settings('title'));
    $caption = t($this->settings('caption'));
    $thead1 = t($this->settings('thead1'));
    $thead2 = t($this->settings('thead2'));
    $footer_label = t($this->settings('footer_label'));

    // Header row.
    $header = [
      $thead1,
      [
        'class' => 'visually-hidden-medium-below',
        'data' => $thead2,

      ]
    ];

    // Rows.
    $json = $this->getQLessJson();
    $queues = $json['data']['queues'];
    $rows = [];
    foreach ($queues as $id => $queue) {
      $stripe_class = $id % 2 == 0 ? 'odd' : 'even';
      array_push($rows, [
        'class' => $stripe_class,
        'data'  => $this->buildRow($queue['name'], $queue['wait_time'], $queue['state']),

      ]);
    }

    // Footer Rows.
    $day = date("F j", strtotime($json['data']['timestamp']));
    $time = date("g:i a", strtotime($json['data']['timestamp']));
    $footer = [
      ['', sprintf('%s: %s at %s', $footer_label, $day, $time)],
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
