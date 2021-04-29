<?php

namespace Drupal\sfgov_qless\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use Drupal\sfgov_qless\Services\QlessValues;

/**
 * Creates the qless page.
 */
class QlessController extends ControllerBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The language manager service.
   *
   * @var Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The form builder.
   *
   * @var Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The configuration factory.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Data from the microservice.
   *
   * @var arrayorNULL
   */
  protected $allData = NULL;

  /**
   * Get Qless Queue data.
   *
   * @var \Drupal\sfgov_qless\Services\QlessValues
   */
  protected $qlessValues;

  /**
   * {@inheritdoc}
   */
  public function __construct(LanguageManager $languageManager, FormBuilderInterface $formBuilder, ConfigFactory $configFactory, ClientInterface $http_client, LoggerChannelFactoryInterface $loggerFactory, QlessValues $qlessValues) {
    $this->languageManager = $languageManager;
    $this->formBuilder = $formBuilder;
    $this->configFactory = $configFactory;
    $this->httpClient = $http_client;
    $this->loggerFactory = $loggerFactory;
    $this->qlessValues = $qlessValues;
    $this->allData = $this->dataFetch();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('form_builder'),
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('sfgov_qless.values')
    );
  }

  /**
   * Get the microservice url from config.
   */
  private function getApiUrl() {
    return 'https://merchant.na4.qless.com/qless/';
  }

  /**
   * Get data from the mircoservice.
   */
  public function dataFetch() {

    try {
      $url = $this->getApiUrl();


      $this->httpClient->get(
        'GET https://[host-environment]/qless/api/v1/wssid'
      );
      $this->httpClient->request( 'POST',
        'https://merchant.na4.qless.com/qless/authenticator' , [
          'query' => [
            'principal' => 'zakiya@chapterthree.com',
            'credentials' => 'hB4%2J!N8xK8',
            'remember' => TRUE
          ],
        ]
      );


      $request = $this->httpClient->get($url . 'api/v1/employee/queues/', [
        'http_errors' => FALSE,
      ]);
      $response = $request->getBody();

      echo '';
    }
    catch (ConnectException | RequestException $e) {
      $response = NULL;
      $this->loggerFactory->get('sfgov_qless')->error('Could not fetch data from %url. %message', [
        '%url' => isset($url) ? $url : 'url',
        '%message' => $e->getMessage(),
      ]);
    }
    return Json::decode($response);
  }

  /**
   * Prepare API data for rendering.
   */
  private function makeApiData($allData) {

    $error_message = $this->qlessValues->settings('error_message');

    return [
      'timestamp' => $allData['data']['generated'],
      'api_url' => $this->getAPIUrl(),
      'error' => $allData == NULL ? $this->t($error_message) : NULL,
    ];
  }

  /**
   * Prepare sites for rendering.
   */
  private function makeQueues($allData) {

    if ($allData == NULL) {
      return [];
    }

    return $allData['data']['queues'];
  }

  /**
   * Display page content.
   */
  public function displayPage() {
    return [
      '#cache' => ['max-age' => 0],
      '#theme' => 'qless',
      '#api_data' => $this->makeApiData($this->allData),
      '#results' => $this->makeQueues($this->allData),
    ];
  }

}
