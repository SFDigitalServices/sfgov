<?php

namespace Drupal\sendgrid_integration_reports\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SendGridReportsController.
 *
 * @package Drupal\sendgrid_integration_reports\Controller
 */
class SendGridReportsController extends ControllerBase {

  /**
   * Api Key of SendGrid.
   *
   * @var array|mixed|null
   */
  protected $apiKey = NULL;

  /**
   * Cache bin of SendGrid Reports module.
   *
   * @var string
   */
  protected $bin = 'sendgrid_integration_reports';

  /**
   * Include the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * SendGridReportsController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, LoggerChannelFactory $logger_factory) {
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    // Load key from variables and throw errors if not there.
    $this->apiKey = $this->configFactory->get('sendgrid_integration.settings')
      ->get('apikey');
    // Display message one time if api key is not set.
    if (empty($this->apiKey)) {
      $this->loggerFactory->get('sendgrid_integration_reports')
        ->warning(t('SendGrid Module is not setup with API key.'));
      $this->messenger->addWarning('Sendgrid Module is not setup with an API key.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('logger.factory')
    );

  }

  /**
   * Sets the cache to sendgrid_integration_reports bin.
   *
   * @param string $cid
   *   Cache Id.
   * @param array $data
   *   The data should be cached.
   */
  protected function setCache($cid, array $data) {
    \Drupal::cache($this->bin)->set($cid, $data);
  }

  /**
   * Returns global reports.
   */
  protected function getStatsGlobal() {
    return $this->getStats('sendgrid_reports_global');
  }

  /**
   * Returns stats categories.
   *
   * @param array $categories
   *   Array of categories.
   * @param string|null $start_date
   *   Start date.
   * @param string|null $end_date
   *   End date.
   * @param bool $refresh
   *   Flag is cache should be refreshed.
   *
   * @return array|bool
   *   Array of stats data.
   */
  public function getStatsCategories(array $categories, $start_date = NULL, $end_date = NULL, $refresh = FALSE) {
    $cid = 'sendgrid_reports_categories';
    // Sanitize the categories array and generate the cache ID.
    if ($categories && is_array($categories)) {
      $categories = array_values($categories);
      $cid .= '_' . implode('_', $categories);
    }
    else {
      $categories = NULL;
    }

    return $this->getStats($cid, $categories, $start_date, $end_date, $refresh);
  }

  /**
   * Returns response from SendGrid.
   *
   * @param string $path
   *   Part of SendGrid endpoint.
   * @param array $query
   *   Query params to the request.
   *
   * @return bool|mixed
   *   Decoded json or FALSE.
   */
  protected function getResponse($path, array $query) {
    // Set headers and create a Guzzle client to communicate with Sendgrid.
    $headers['Authorization'] = 'Bearer ' . $this->apiKey;
    $clienttest = new Client([
      'base_uri' => 'https://api.sendgrid.com/v3/',
      'headers' => $headers,
    ]);

    // Lets attempt the request and catch an error if it fails.
    try {
      $response = $clienttest->get($path, ['query' => $query]);
    }
    catch (ClientException $e) {
      $code = Xss::filter($e->getCode());
      $this->loggerFactory->get('sendgrid_integration_reports')
        ->error(t('SendGrid Reports module failed to receive data. HTTP Error Code @errno', ['@errno' => $code]));
      $this->messenger->addError(t('SendGrid Reports module failed to receive data. See logs.'));
      return FALSE;
    }
    // Sanitize return before using in Drupal.
    $body = Xss::filter($response->getBody());
    return json_decode($body);
  }

  /**
   * Returns reports.
   */
  public function getReports() {
    $stats = $this->getStatsGlobal();
    $settings = [];
    $stats['global'] = isset($stats['global']) ? $stats['global'] : [];

    foreach ($stats['global'] as $items) {
      $settings['global'][] = [
        'date' => $items['date'],
        'opens' => $items['opens'],
        'clicks' => $items['clicks'],
        'delivered' => $items['delivered'],
        'spam_reports' => $items['spam_reports'],
        'spam_report_drops' => $items['spam_report_drops'],
      ];
    }

    $render = [
      '#attached' => [
        'library' => [
          'sendgrid_integration_reports/googlejsapi',
          'sendgrid_integration_reports/main',
        ],
        'drupalSettings' => [
          'sendgrid_integration_reports' => $settings,
        ],
      ],
      'message' => [
        '#markup' => t('The following reports are the from the Global Statistics provided by SendGrid. For more comprehensive data, please visit your @dashboard. @cache to ensure the data is current. @settings to alter the time frame of this data.',
          [
            '@dashboard' => Link::fromTextAndUrl(t('SendGrid Dashboard'), Url::fromUri('//app.sendgrid.com/'))
              ->toString(),
            '@cache' => Link::createFromRoute(t('Clear your cache'), 'system.performance_settings')
              ->toString(),
            '@settings' => Link::createFromRoute(t('Change your settings'), 'sendgrid_integration_reports.settings_form')
              ->toString(),
          ]
        ),
      ],
      'volume' => [
        '#prefix' => '<h2>' . t('Sending Volume') . '</h2>',
        '#markup' => '<div id="sendgrid-global-volume-chart"></div>',
      ],
      'spam' => [
        '#prefix' => '<h2>' . t('Spam Reports') . '</h2>',
        '#markup' => '<div id="sendgrid-global-spam-chart"></div>',
      ],
    ];
    $browserstats = $this->getStatsBrowser();

    $rows = [];
    foreach ($browserstats as $key => $value) {
      $rows[] = [$key, $value];
    }
    $headerbrowser = [
      t('Browser'),
      t('Click Count'),
    ];
    $render['browsers'] = [
      '#prefix' => '<h2>' . t('Browser Statistics') . '</h2>',
      '#theme' => 'table',
      '#header' => $headerbrowser,
      '#rows' => $rows,
      'attributes' => ['width' => '75%'],
    ];

    $devicestats = $this->getStatsDevices();
    $rowsdevices = [];
    foreach ($devicestats as $key => $value) {
      $rowsdevices[] = [
        $key,
        $value,
      ];
    }
    $headerdevices = [
      t('Device'),
      t('Open Count'),
    ];
    $render['devices'] = [
      '#prefix' => '<h2>' . t('Device Statistics') . '</h2>',
      '#theme' => 'table',
      '#header' => $headerdevices,
      '#rows' => $rowsdevices,
      'attributes' => ['width' => '75%'],
    ];

    return $render;
  }

  /**
   * Returns stats.
   *
   * @param string $cid
   *   Cache Id.
   * @param array $categories
   *   Array of categories.
   * @param string|null $start_date
   *   Start date.
   * @param string|null $end_date
   *   End date.
   * @param bool $refresh
   *   Flag is cache should be refreshed.
   *
   * @return array|bool
   *   Array of stats data.
   */
  public function getStats($cid, array $categories = [], $start_date = NULL, $end_date = NULL, $refresh = FALSE) {

    if (!$refresh && $cache = \Drupal::cache($this->bin)->get($cid)) {
      return $cache->data;
    }

    // Load key from variables and throw errors if not there.
    if (empty($this->apiKey)) {
      return [];
    }

    // Get config.
    $config = $this->configFactory->get('sendgrid_integration_reports.settings')
      ->get();
    if ($start_date) {
      $start_date = date('Y-m-d', strtotime($start_date));
    }
    else {
      // Set start date and end date for global stats - default 30 days back.
      $start_date = empty($config['start_date']) ? date('Y-m-d', strtotime('today - 30 days')) : $config['start_date'];
    }

    if ($end_date) {
      $end_date = date('Y-m-d', strtotime($end_date));
    }
    else {
      // Set the end date which defaults to today.
      $end_date = empty($config['end_date']) ? date('Y-m-d', strtotime('today')) : $config['end_date'];
    }

    // Set aggregation of stats - default day.
    $aggregated_by = isset($config['aggregated_by']) ? $config['aggregated_by'] : 'day';
    $path = 'stats';
    $query = [
      'start_date' => $start_date,
      'end_date' => $end_date,
      'aggregated_by' => $aggregated_by,
    ];

    if ($categories) {
      $path = 'categories/stats';
      $query['categories'] = $categories;
      $query_str = http_build_query($query, NULL, '&', PHP_QUERY_RFC3986);
      $query = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $query_str);
    }
    // Lets attempt the request and catch an error if it fails.
    $stats_data = $this->getResponse($path, $query);

    $data = [];
    foreach ($stats_data as $item) {
      $data['global'][] = [
        'date' => $item->date,
        'opens' => $item->stats[0]->metrics->opens,
        'processed' => $item->stats[0]->metrics->processed,
        'requests' => $item->stats[0]->metrics->requests,
        'clicks' => $item->stats[0]->metrics->clicks,
        'delivered' => $item->stats[0]->metrics->delivered,
        'deferred' => $item->stats[0]->metrics->deferred,
        'unsubscribes' => $item->stats[0]->metrics->unsubscribes,
        'unsubscribe_drops' => $item->stats[0]->metrics->unsubscribe_drops,
        'invalid_emails' => $item->stats[0]->metrics->invalid_emails,
        'bounces' => $item->stats[0]->metrics->bounces,
        'bounce_drops' => $item->stats[0]->metrics->bounce_drops,
        'unique_clicks' => $item->stats[0]->metrics->unique_clicks,
        'blocks' => $item->stats[0]->metrics->blocks,
        'spam_report_drops' => $item->stats[0]->metrics->spam_report_drops,
        'spam_reports' => $item->stats[0]->metrics->spam_reports,
        'unique_opens' => $item->stats[0]->metrics->unique_opens,
      ];
    }

    // Save data to cache.
    $this->setCache($cid, $data);

    return $data;
  }

  /**
   * Returns browser stats.
   */
  public function getStatsBrowser() {
    $cid = 'sendgrid_reports_browsers';
    if ($cache = \Drupal::cache($this->bin)->get($cid)) {
      return $cache->data;
    }

    // Load key from variables and throw errors if not there.
    if (empty($this->apiKey)) {
      return [];
    }

    // Set start date and end date for global stats - default 30 days back.
    $start_date = empty($config['start_date']) ? date('Y-m-d', strtotime('today - 30 days')) : $config['start_date'];
    $end_date = empty($config['end_date']) ? date('Y-m-d', strtotime('today')) : $config['end_date'];
    // Set aggregation of stats - default day.
    $aggregated_by = isset($config['aggregated_by']) ? $config['aggregated_by'] : 'day';
    $path = 'browsers/stats';
    $query = [
      'start_date' => $start_date,
      'end_date' => $end_date,
      'aggregated_by' => $aggregated_by,
    ];

    // Lets try and retrieve the browser statistics.
    $statsdata = $this->getResponse($path, $query);
    $data = [];
    // Determine all browsers. Nested foreach to
    // iterate over all data returned per aggregation.
    foreach ($statsdata as $item) {
      foreach ($item->stats as $inneritem) {
        if (array_key_exists($inneritem->name, $data)) {
          $data[$inneritem->name] += $inneritem->metrics->clicks;
        }
        else {
          $data[$inneritem->name] = $inneritem->metrics->clicks;
        }
      }
    }

    // Save data to cache.
    $this->setCache($cid, $data);

    return $data;
  }

  /**
   * Returns devices stats.
   */
  public function getStatsDevices() {
    $cid = 'sendgrid_reports_devices';
    if ($cache = \Drupal::cache($this->bin)->get($cid)) {
      return $cache->data;
    }

    // Load key from variables and throw errors if not there.
    if (empty($this->apiKey)) {
      return FALSE;
    }

    // Set start date and end date for global stats - default 30 days back.
    $start_date = empty($config['start_date']) ? date('Y-m-d', strtotime('today - 30 days')) : $config['start_date'];
    $end_date = empty($config['end_date']) ? date('Y-m-d', strtotime('today')) : $config['end_date'];
    // Set aggregation of stats - default day.
    $aggregated_by = isset($config['aggregated_by']) ? $config['aggregated_by'] : 'day';

    $path = 'devices/stats';
    $query = [
      'start_date' => $start_date,
      'end_date' => $end_date,
      'aggregated_by' => $aggregated_by,
    ];

    // Lets try and retrieve the browser statistics.
    $statsdata = $this->getResponse($path, $query);
    $data = [];
    // Determine all browsers. Nested foreach to
    // iterate over all data returned per aggregation.
    foreach ($statsdata as $item) {
      foreach ($item->stats as $inneritem) {
        if (array_key_exists($inneritem->name, $data)) {
          $data[$inneritem->name] += $inneritem->metrics->opens;
        }
        else {
          $data[$inneritem->name] = $inneritem->metrics->opens;
        }
      }
    }

    // Save data to cache.
    $this->setCache($cid, $data);

    return $data;
  }

}
