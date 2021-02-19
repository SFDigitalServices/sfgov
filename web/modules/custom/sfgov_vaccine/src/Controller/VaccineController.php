<?php

namespace Drupal\sfgov_vaccine\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Template\Attribute;

/**
 * Class VaccineController.
 */
class VaccineController extends ControllerBase {

  /**
   * Drupal\Core\Cache\Context\CacheContextInterface definition.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextInterface
   */
  protected $cacheContextIp;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->cacheContextIp = $container->get('cache_context.ip');
    return $instance;
  }

  private function makeTitle() {
    // @todo Create admin form.
    return t("COVID-19 vaccine sites");
  }

  private function datafecth() {
    // @todo Figure out what to do if this fails.
    /** @var \GuzzleHttp\Client $client */
    $client = \Drupal::service('http_client_factory')->fromOptions([
      'base_uri' => 'https://vaccination-site-microservice-git-add-sample-json-fixture.sfds.vercel.app/',
    ]);

    // Optional language query.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $query = NULL;
    if ($language != 'en') {
      $query = [
        'query' => [
          'lang' => $language,
        ]];
    }

    // @todo - Creat ability to set value in $settings_array.
    $response = $client->get('api/v1/test_sites', $query);
    return Json::decode($response->getBody());
  }

  private function makeFilters() {
    return \Drupal::formBuilder()->getForm('\Drupal\sfgov_vaccine\Form\FilterSitesForm');
  }

  private function makeResults() {

    $all_data = $this->datafecth();
    $generated = $all_data['data']['generated'];
    $sites = $all_data['data']['sites'];
    $results = [];
    foreach ($sites as $site_id => $site_data ) {

      // Pre-prep languages.
      $site_data_languages = $site_data['access']['languages'];
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
        ]
      ];

      $languages = [];
      foreach ($languages_with_text as $key => $value){
        if ($value['boolean'] == TRUE) {
          array_push($languages, $value['text']);
        }
      }

      // Pre-prep Eligibility.
      $site_data_eligibility = $site_data['eligibility'];
      $eligibility_with_text = [
        '65_and_over' => [
          'boolean' => $site_data_eligibility["65_and_over"],
          'text' => t('65 and over')
        ],
        'healthcare_workers' => [
          'boolean' => $site_data_eligibility["healthcare_workers"],
          'text' => t('Healthcare workers')
        ],
        'education_and_childcare' => [
          'boolean' => $site_data_eligibility["education_and_childcare"],
          'text' => t('Education and childcare')
        ],
        'agriculture_and_food' => [
          'boolean' => $site_data_eligibility["agriculture_and_food"],
          'text' => t('Agriculture and food')
        ],
        'second_dose_only' => [
          'boolean' => $site_data_eligibility["second_dose_only"],
          'text' => t('Second dose')
        ],
        'emergency_services' => [
          'boolean' => $site_data_eligibility["emergency_services"],
          'text' => t('Emergency services')
        ]
      ];

      // @todo make this a reusable method for languages and eligibility,
      // access_mode.
      $eligibilities = [];
      foreach ($eligibility_with_text as $key => $value){
        if ($value['boolean'] == TRUE) {
          array_push($eligibilities, $value['text']);
        }
      }

      // Pre-prep access mode.
      $site_data_access_mode = $site_data['access_mode'];
      $access_mode_with_text = [
        'walk' => [
          'boolean' => $site_data_access_mode["walk"],
          'text' => t('Walk-thru')
        ],
        'drive' => [
          'boolean' => $site_data_access_mode["drive"],
          'text' => t('Drive-thru')
        ],
        'wheelchair' => [
          'boolean' => $site_data['access']['wheelchair'],
          'text' => t('Wheelchair accessible'),
        ],
      ];

      $access_modes = [];
      foreach ($access_mode_with_text as $key => $value){
        if ($value['boolean'] == TRUE) {
          array_push($access_modes, $value['text']);
        }
      }

      // Usable variables.
      $available = $site_data['appointments']['available']; // Boolean.
      $site_name = $site_data['name'];
      $restrictions = $site_data['open_to']['everyone'];
      $restrictions_text = $site_data['open_to']['text'];
      $address_text = $site_data['location']['address'];
      $address_url = $site_data['location']['url'];
      $info_url = $site_data['info']['url'];

      // Map results.
      $result = [
        'site_name' => $site_name,
        'attributes' => new Attribute([
          'class' => ['sfgov-service-card', 'vaccine-site'],
          'data-available' => $available ? 'true': 'false',
          'data-restrictions' => $restrictions ? 'true' : 'false',
        ]),
        'generated' => date( "F j, Y, g:i a", strtotime($generated)),
        'restrictions' => $restrictions_text,
        'address_text' => $address_text,
        'address_url' => $address_url,
        'languages' => $languages,
        'eligibilities' => $eligibilities,
        'access_modes' => $access_modes,
        'info_url' => $info_url,
        'available' => $available ? t('Appointments Available as of') : t('No appointments as of'),
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
      '#theme' => 'vaccine-widget',
      '#page_title' => $this->makeTitle(),
      '#filters' => $this->makeFilters(),
      '#results' => $this->makeResults(),
      ];
  }
}
