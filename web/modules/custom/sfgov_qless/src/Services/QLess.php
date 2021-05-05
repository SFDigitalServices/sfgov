<?php

namespace Drupal\sfgov_qless\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
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
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Data from the microservice.
   *
   * @var arrayorNULL
   */
  protected $allData = NULL;

  /**
   * Class constructor.
   */
  public function __construct(State $state, ConfigFactory $configFactory, ClientInterface $http_client, LoggerChannelFactoryInterface $loggerFactory) {
    $this->state = $state;
    $this->configFactory = $configFactory;
    $this->httpClient = $http_client;
    $this->loggerFactory = $loggerFactory;
    $this->allData = $this->dataFetch();
  }

  /**
   * Create QLess object.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('logger.factory')
    );
  }

  /**
   * Get config settings for this module.
   */
  public function settings($value) {
    return $this->configFactory->get('sfgov_qless.settings')->get($value);
  }

  /**
   * Get the microservice url from config.
   */
  private function getApiUrl() {
    return $this->settings('api_url');
  }

  /**
   * Get data from the mircoservice.
   */
  public function dataFetch() {

    $url = '';

    try {
      $url = $this->getApiUrl();
      $request = $this->httpClient->get($url, [
        'http_errors' => FALSE,
      ]);
      $response = $request->getBody();
    }
    catch (ConnectException | RequestException $e) {
      $response = NULL;
      $this->loggerFactory->get('sfgov_qless')->error('Could not fetch data from %url. %message', [
        '%url' => $url ?: 'url',
        '%message' => $e->getMessage(),
      ]);
    }
    return Json::decode($response);
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

    if ($this->allData == NULL) {
      return NULL;
    }

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
      ],
    ];

    // Rows.
    $json = $this->allData;
    $queues = $json['data']['queues'];
    $rows = [];

    foreach ($queues as $id => $queue) {
      $stripe_class = $id % 2 == 0 ? 'odd' : 'even';
      array_push($rows, [
        'class' => $stripe_class,
        'data'  => $this->buildRow(
          $queue['description'] ?? $queue['description'] ?? '',
          $queue['wait_time'] ?? $queue['wait_time'] ?? '',
          $queue['state'] ?? $queue['state'] ?? ''
        ),
      ]);
    }

    // Footer Rows.
    $footer = NULL;
    if (isset($json['data']['generated'])) {
      $day = date("F j", strtotime($json['data']['generated']));
      $time = date("g:i a", strtotime($json['data']['generated']));
      $footer = [
        ['', sprintf('%s: %s at %s', $footer_label, $day, $time)],
      ];
    }

    // Render.
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
