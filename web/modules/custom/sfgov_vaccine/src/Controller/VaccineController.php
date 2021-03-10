<?php

namespace Drupal\sfgov_vaccine\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Template\Attribute;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;

/**
 * Creates the vaccine sites page.
 */
class VaccineController extends ControllerBase {

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
   * {@inheritdoc}
   */
  public function __construct(LanguageManager $languageManager, FormBuilderInterface $formBuilder, ConfigFactory $configFactory, ClientInterface $http_client) {
    $this->languageManager = $languageManager;
    $this->formBuilder = $formBuilder;
    $this->configFactory = $configFactory;
    $this->httpClient = $http_client;
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
      $container->get('http_client')
    );
  }

  /**
   * Get the microservice url from config.
   */
  private function getAPIUrl() {
    return $this->configFactory->get('sfgov_vaccine.settings')->get('api_url');
  }

  /**
   * Prepare page title for rendering.
   */
  private function makeTitle() {
    // @todo Create admin form.
    return $this->t("COVID-19 vaccine sites");
  }

  /**
   * Get data from the mircoservice.
   */
  public function dataFetch() {

    try {
      $language = $this->languageManager()->getCurrentLanguage()->getId();
      $url = $this->getAPIUrl() . '?lang=' . $language;
      $request = $this->httpClient->get($url, [
        'http_errors' => FALSE,
      ]);
      $response = $request->getBody();
    }
    catch (ConnectException $e) {
      $response = NULL;
    }
    return Json::decode($response);
  }

  /**
   * Prepare API data for rendering.
   */
  private function makeAPIData($allData) {

    $error_message = $this->t('We are having trouble reaching the service right now. Please try again later.');

    return [
      'timestamp' => $allData['data']['generated'],
      'api_url' => $this->getAPIUrl(),
      'error' => $allData == NULL ? $error_message : NULL,
    ];
  }

  /**
   * Get the filter form.
   */
  private function makeFilters($allData) {
    return $this->formBuilder->getForm('\Drupal\sfgov_vaccine\Form\FilterSitesForm');
  }

  /**
   * Prepare sites for rendering.
   */
  private function makeResults($allData) {

    if ($allData == NULL) {
      return [];
    }

    $sites = $allData['data']['sites'];
    $results = [];
    foreach ($sites as $site_id => $site_data) {

      // Pre-prep languages.
      $site_data_languages = $site_data['access']['languages'];
      $site_data_remote_translation = $site_data['access']["remote_translation"];
      $languages_with_text = [
        'en' => [
          'boolean' => $site_data_languages["en"],
          'text' => $this->t('English'),
        ],
        'es' => [
          'boolean' => $site_data_languages["es"],
          'text' => $this->t('Spanish'),
        ],
        'zh' => [
          'boolean' => $site_data_languages["zh"],
          'text' => $this->t('Chinese'),
        ],
        'fil' => [
          'boolean' => $site_data_languages["fil"],
          'text' => $this->t('Filipino'),
        ],
        'vi' => [
          'boolean' => $site_data_languages["vi"],
          'text' => $this->t('Vietnamese'),
        ],
        'ru' => [
          'boolean' => $site_data_languages["ru"],
          'text' => $this->t('Russian'),
        ],
        'rt' => [
          'boolean' => $site_data_remote_translation['available'],
          'text' => $site_data_remote_translation['available'] ? $site_data_remote_translation['info'] : NULL,
        ],
      ];

      $languages = [];
      $language_keys = [];
      foreach ($languages_with_text as $key => $value) {
        if ($value['boolean'] === TRUE) {

          array_push($languages, $value['text']);
          array_push($language_keys, $key);
        }
      }
      array_push($language_keys, 'all');

      // Pre-prep Eligibility.
      $site_data_eligibility = $site_data['eligibility'];
      $eligibility_with_text = [
        'sf' => [
          'boolean' => $site_data_eligibility["65_and_over"],
          'text' => $this->t('65 and over'),
        ],
        'hw' => [
          'boolean' => $site_data_eligibility["healthcare_workers"],
          'text' => $this->t('Healthcare workers'),
        ],
        'ec' => [
          'boolean' => $site_data_eligibility["education_and_childcare"],
          'text' => $this->t('Education and childcare'),
        ],
        'af' => [
          'boolean' => $site_data_eligibility["agriculture_and_food"],
          'text' => $this->t('Agriculture and food'),
        ],
        'sd' => [
          'boolean' => $site_data_eligibility["second_dose_only"],
          'text' => $this->t('Second dose only'),
        ],
        'es' => [
          'boolean' => $site_data_eligibility["emergency_services"],
          'text' => $this->t('Emergency services'),
        ],
      ];

      // @todo make this a reusable method for languages and eligibility,
      // access_mode.
      $eligibilities = [];
      $eligibility_keys = [];
      foreach ($eligibility_with_text as $key => $value) {
        if ($value['boolean'] == TRUE) {
          array_push($eligibilities, $value['text']);
          array_push($eligibility_keys, $key);
        }
      }
      array_push($eligibility_keys, 'all');

      // Pre-prep access mode.
      $site_data_access_mode = $site_data['access_mode'];
      $access_mode_with_text = [
        'wa' => [
          'boolean' => $site_data_access_mode["walk"],
          'text' => $this->t('Walk-thru'),
        ],
        'dr' => [
          'boolean' => $site_data_access_mode["drive"],
          'text' => $this->t('Drive-thru'),
        ],
        'wh' => [
          'boolean' => $site_data['access']['wheelchair'],
          'text' => $this->t('Wheelchair accessible'),
        ],
      ];

      $access_modes = [];
      $access_mode_keys = [];
      foreach ($access_mode_with_text as $key => $value) {
        if ($value['boolean'] == TRUE) {
          array_push($access_modes, $value['text']);
          if ($key != 'wh') {
            array_push($access_mode_keys, $key);
          }
        }
      }
      array_push($access_mode_keys, 'all');

      // Usable variables.
      $info_url = NULL;
      $booking_info = NULL;

      $available = $site_data['appointments']['available'];
      if ($available === TRUE) {
        $available = 'yes';
      } else if($available === FALSE) {
        $available = 'no';
      } else if ($available === NULL) {
        $available = 'null';
      }

      if (isset($site_data['info']) && isset($site_data['info']['url'])) {
        $info_url = $site_data['info']['url'];
      }

      if (isset($site_data['booking']['info'])) {
        $booking_info = $site_data['booking']['info'];
      }
      $last_updated = $site_data['appointments']['last_updated'];
      $site_name = $site_data['name'];
      $restrictions = $site_data['open_to']['everyone'];
      $restrictions_text = $site_data['open_to']['text'];
      $address_text = $site_data['location']['address'];
      $address_url = $site_data['location']['url'];
      $booking_url = $site_data['booking']['url'];
      $booking_dropins = $site_data['booking']['dropins'];
      $wheelchair = $site_data['access']['wheelchair'];

      // Map results.
      $result = [
        'site_name' => $site_name,
        'attributes' => new Attribute([
          'class' => ['sfgov-service-card', 'vaccine-site', 'no-hover'],
          // Single Selects.
          'data-restrictions' => $restrictions ? 0 : 1,
          'data-available' => $available,
          'data-wheelchair' => $wheelchair ? 1 : 0,
          // Multi-selects.
          'data-language' => $language_keys ? implode('-', $language_keys) : 'en-es-zh-fil-vi-ru-rt-all',
          'data-access-mode' => implode('-', $access_mode_keys),
          'data-eligibility' => implode('-', $eligibility_keys),
        ]),
        'last_updated' => date("F j, Y, g:i a", strtotime($last_updated)),
        'restrictions' => $restrictions_text,
        'address_text' => $address_text,
        'address_url' => $address_url,
        'languages' => $languages,
        'eligibilities' => $eligibilities,
        'access_modes' => $access_modes,
        'info_url' => $info_url,
        'available' => $available,
        'booking_url' => $booking_url,
        'booking_dropins' => $booking_dropins,
        'booking_info' => $booking_info,
      ];
      $results[] = $result;
    }

    return $results;
  }

  /**
   * Display page content.
   */
  public function displayPage() {
    return [
      '#cache' => ['max-age' => 0],
      '#theme' => 'vaccine_widget',
      '#page_title' => $this->makeTitle(),
      '#api_data' => $this->makeAPIData($this->allData),
      '#filters' => $this->makeFilters($this->allData),
      '#results' => $this->makeResults($this->allData),
    ];
  }

}
