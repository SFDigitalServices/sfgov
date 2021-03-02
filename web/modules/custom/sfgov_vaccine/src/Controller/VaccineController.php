<?php

namespace Drupal\sfgov_vaccine\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Template\Attribute;

/**
 * Class VaccineController.
 */
class VaccineController extends ControllerBase {

  /**
   * @var \Drupal\http_client_manager\HttpClientInterface
   */
  protected $languageManager;

  /**
   * @var Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  protected $configFactory;


public function __construct(LanguageManager $languageManager, FormBuilderInterface $formBuilder, ConfigFactory $configFactory) {
    $this->languageManager = $languageManager;
    $this->formBuilder = $formBuilder;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('form_builder'),
      $container->get('config.factory')
    );
  }

  private function getAPIQuery() {
    return $this->configFactory->get('sfgov_vaccine.settings')->get('query');
  }

  private function getAPIBaseUri() {
    return $this->configFactory->get('sfgov_vaccine.settings')->get('base_uri');
  }

  private function makeTitle() {
    // @todo Create admin form.
    return t("COVID-19 vaccine sites");
  }

  private function dataFetch() {
    // @todo Figure out what to do if this fails.
    /** @var \GuzzleHttp\Client $client */
    $client = \Drupal::service('http_client_factory')->fromOptions([
      'base_uri' => $this->getAPIBaseUri(),
    ]);

    // Optional language query.
    $language = $this->languageManager()->getCurrentLanguage()->getId();
    $language_query = NULL;
    if ($language != 'en') {
      $language_query = [
        'query' => [
          'lang' => $language,
        ]];
    }

    $response = $client->get($this->getAPIQuery(), $language_query);
    return Json::decode($response->getBody());
  }

  private function makeAPIData() {
    $all_data = $this->dataFetch();

    return [
      'timestamp' => $all_data['data']['generated'],
      'base_uri' => $this->getAPIBaseUri(),
      'query' => $this->getAPIQuery(),
    ];
  }

  private function makeFilters() {
    return $this->formBuilder->getForm('\Drupal\sfgov_vaccine\Form\FilterSitesForm');
  }

  private function makeResults() {

    $all_data = $this->dataFetch();
    $sites = $all_data['data']['sites'];
    $results = [];
    foreach ($sites as $site_id => $site_data ) {

      // Pre-prep languages.
      $site_data_languages = $site_data['access']['languages'];
      $site_date_remote_translation = $site_data['access']["remote_translation"];
      $languages_with_text = [
        'en' => [
          'boolean' => $site_data_languages["en"],
          'text' => t('English')
         ],
        'es' => [
          'boolean' => $site_data_languages["es"],
          'text' => t('Spanish')
        ],
        'zh' => [
          'boolean' => $site_data_languages["zh"],
          'text' => t('Chinese')
        ],
        'fil' => [
          'boolean' => $site_data_languages["fil"],
          'text' => t('Filipino')
        ],
        'vi' => [
          'boolean' => $site_data_languages["vi"],
          'text' => t('Vietnamese')
        ],
        'ru' => [
          'boolean' => $site_data_languages["ru"],
          'text' => t('Russian')
        ],
        'rt' => [
          'boolean' => $site_date_remote_translation['available'],
          'text' => $site_date_remote_translation['available'] ? t($site_date_remote_translation['info']) : NULL
        ],
      ];

      $languages = [];
      $language_keys = [];
      foreach ($languages_with_text as $key => $value){
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
          'text' => t('65 and over')
        ],
        'hw' => [
          'boolean' => $site_data_eligibility["healthcare_workers"],
          'text' => t('Healthcare workers')
        ],
        'ec' => [
          'boolean' => $site_data_eligibility["education_and_childcare"],
          'text' => t('Education and childcare')
        ],
        'af' => [
          'boolean' => $site_data_eligibility["agriculture_and_food"],
          'text' => t('Agriculture and food')
        ],
        'sd' => [
          'boolean' => $site_data_eligibility["second_dose_only"],
          'text' => t('Second dose only')
        ],
        'es' => [
          'boolean' => $site_data_eligibility["emergency_services"],
          'text' => t('Emergency services')
        ]
      ];

      // @todo make this a reusable method for languages and eligibility,
      // access_mode.
      $eligibilities = [];
      $eligibility_keys = [];
      foreach ($eligibility_with_text as $key => $value){
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
          'text' => t('Walk-thru')
        ],
        'dr' => [
          'boolean' => $site_data_access_mode["drive"],
          'text' => t('Drive-thru')
        ],
        'wh' => [
          'boolean' => $site_data['access']['wheelchair'],
          'text' => t('Wheelchair accessible'),
        ],
      ];

      $access_modes = [];
      $access_mode_keys = [];
      foreach ($access_mode_with_text as $key => $value){
        if ($value['boolean'] == TRUE) {
          array_push($access_modes, $value['text']);
          if ($key != 'wh') {
            array_push($access_mode_keys, $key);}
        }
      }
      array_push($access_mode_keys, 'all');

      // Usable variables.
      $available = NULL;
      $info_url = NULL;
      $booking_info = NULL;

      if (isset($site_data['appointments']) && isset($site_data['appointments']['available'])) {
        $available = $site_data['appointments']['available'];
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
          'data-available' => $available ? 1 : 0,
          'data-wheelchair' => $wheelchair ? 1 : 0,
          // Multi-selects.
          'data-language' => $language_keys ? implode('-',$language_keys) : 'en-es-zh-fil-vi-ru-rt-all',
          'data-access-mode' => implode('-',$access_mode_keys),
          'data-eligibility' => implode('-',$eligibility_keys),
        ]),
        'last_updated' => date( "F j, Y, g:i a", strtotime($last_updated)),
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
   * Display Page.
   *
   * @return array
   *   Return Render Array.
   */
  public function displayPage() {
    return [
      '#cache' => ['max-age' => 0,],
      '#theme' => 'vaccine_widget',
      '#page_title' => $this->makeTitle(),
      '#api_data' => $this->makeAPIData(),
      '#filters' => $this->makeFilters(),
      '#results' => $this->makeResults(),
      ];
  }
}
