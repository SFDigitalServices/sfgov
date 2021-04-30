<?php

namespace Drupal\sfgov_vaccine\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Template\Attribute;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use Drupal\sfgov_vaccine\Services\VaxValues;

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
   * Get Vaccine-related data.
   *
   * @var \Drupal\sfgov_vaccine\Services\VaxValues
   */
  protected $vaxValues;


  /**
   * {@inheritdoc}
   */
  public function __construct(LanguageManager $languageManager, FormBuilderInterface $formBuilder, ConfigFactory $configFactory, ClientInterface $http_client, LoggerChannelFactoryInterface $loggerFactory, VaxValues $vaxValues) {
    $this->languageManager = $languageManager;
    $this->formBuilder = $formBuilder;
    $this->configFactory = $configFactory;
    $this->httpClient = $http_client;
    $this->loggerFactory = $loggerFactory;
    $this->vaxValues = $vaxValues;
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
      $container->get('sfgov_vaccine.values')
    );
  }

  /**
   * Get the microservice url from config.
   */
  private function getAPIUrl() {
    return $this->vaxValues->settings('api_url');
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
    catch (ConnectException | RequestException $e) {
      $response = NULL;
      $this->loggerFactory->get('sfgov_vaccine')->error('Could not fetch data from %url. %message', [
        '%url' => isset($url) ? $url : 'url',
        '%message' => $e->getMessage(),
      ]);
    }
    return Json::decode($response);
  }

  /**
   * Prepare API data for rendering.
   */
  private function makeAPIData($allData) {

    $error_message = $this->vaxValues->settings('error_message');

    return [
      'timestamp' => $allData['data']['generated'],
      'api_url' => $this->getAPIUrl(),
      'error' => $allData == NULL ? $this->t($error_message) : NULL,
    ];
  }

  /**
   * Get the filter form.
   */
  private function makeFilters() {
    return $this->formBuilder->getForm('\Drupal\sfgov_vaccine\Form\FilterSitesForm');
  }

  /**
   * Prepare each site's data-access-mode value.
   */
  private function getSiteAccessModeKeys($site_data) {

    $access_mode_options = [
      'walk' => $site_data['access_mode']["walk"],
      'drive' => $site_data['access_mode']["drive"],
    ];

    $keys = [];
    foreach ($access_mode_options as $key => $value) {
      if ($value === TRUE) {
        $key = $this->vaxValues->settings('access_mode.' . $key . '.short_key');
        array_push($keys, $key);
      }
    }
    array_push($keys, 'all');

    return $keys;
  }

  /**
   * Prepare each site's data-language value.
   */
  private function getSiteLanguageKeys($access_data) {
    $keys = [];
    $site_data_languages = $access_data['languages'];
    $site_data_remote_translation = $access_data["remote_translation"];
    foreach ($site_data_languages as $short_key => $boolean) {
      if ($boolean === TRUE) {
        array_push($keys, $short_key);
      }
    }
    if ($site_data_remote_translation['available']) {
      array_push($keys, 'rt');
    }
    array_push($keys, 'all');
    return $keys;
  }

  /**
   * Prepare each site's access mode text.
   */
  private function getSiteAccessModeText($site_data) {
    $access_mode_options = [
      'walk' => $site_data['access_mode']["walk"],
      'drive' => $site_data['access_mode']["drive"],
      'wheelchair' => $site_data['access']['wheelchair'],
    ];

    $printed = [];
    foreach ($access_mode_options as $key => $value) {
      if ($value === TRUE) {
        $text = $this->vaxValues->settings('access_mode.' . $key . '.text');
        array_push($printed, $this->t($text));
      }
    }

    return $printed;
  }

  /**
   * Prepare each site's language text.
   */
  private function getSiteLanguageText($access_data) {

    // Get remote vars.
    $site_data_remote_translation = $access_data["remote_translation"];
    $site_data_languages = $access_data['languages'];

    $printed_languages = [];
    foreach ($site_data_languages as $short_key => $boolean) {
      $language_label = $this->vaxValues->settings(sprintf('languages.%s.site_label', $short_key));
      if ($boolean === TRUE && !empty($language_label)) {
        array_push(
          $printed_languages, $this->t($language_label));
      }
    }

    $remote_asl = NULL;
    if ($site_data_remote_translation['available']) {
      array_push($printed_languages, $site_data_remote_translation['info']);
      $remote_asl = (strpos($site_data_remote_translation['info'], 'ASL')) ? TRUE : FALSE;
    }

    return [
      'printed_languages' => $printed_languages,
      'remote_asl' => $remote_asl,
    ];
  }

  /**
   * Prepare each site's eligibility text.
   */
  private function getSiteEligibilityText($site_data, $group) {

    $printed = [];
    if (isset($site_data[$group]['allowed'])) {
      $site_minor_status = $site_data[$group]['allowed'] ? 'true': 'false';
      $text = $this->vaxValues->settings($group . '.' . $site_minor_status . '_text');
      $printed_value = $this->t($text);
      array_push($printed, $printed_value);
    }
    return $printed;
  }

  /**
   * Prepare sites for rendering.
   */
  private function makeResults($allData) {

    if ($allData == NULL) {
      return [];
    }

    $allowed_html_tags = [
      'br',
      'a',
      'em',
      'strong',
      'i',
      'b',
      'ul',
      'ol',
      'li',
    ];

    $sites = $allData['data']['sites'];
    $results = [];
    foreach ($sites as $site_id => $site_data) {

      $eligibility_text = $this->getSiteEligibilityText($site_data, 'minors');
      $language_keys = $this->getSiteLanguageKeys($site_data['access']);
      $language_text = $this->getSiteLanguageText($site_data['access']);
      $access_mode_keys = $this->getSiteAccessModeKeys($site_data);
      $access_mode_text = $this->getSiteAccessModeText($site_data);

      // Usable variables.
      $info_url = NULL;

      $available = $site_data['appointments']['available'];
      if ($available === TRUE) {
        $available = 'yes';
      }
      elseif ($available === FALSE) {
        $available = 'no';
      }
      elseif ($available === NULL) {
        $available = 'null';
      }

      if (isset($site_data['info']) && isset($site_data['info']['url'])) {
        $info_url = $site_data['info']['url'];
      }

      $booking = isset($site_data['booking']) ? $site_data['booking'] : NULL;
      $booking['safe_info'] = isset($booking['info']) ? Xss::filter($booking['info'], $allowed_html_tags) : NULL;

      $last_updated = $site_data['appointments']['last_updated'];
      $site_name = $site_data['name'];
      $site_id = isset($site_data['site_id']) ? $site_data['site_id'] : NULL;
      $restrictions = $site_data['open_to']['everyone'];
      $restrictions_text = $site_data['open_to']['text'] ? Xss::filter($site_data['open_to']['text'], $allowed_html_tags) : NULL;
      $location = $site_data['location'];
      $wheelchair = $site_data['access']['wheelchair'];
      $brands = $site_data['brands'];

      // Map results.
      $result = [
        'site_name' => $site_name,
        'attributes' => new Attribute([
          'class' => ['vaccine-site', 'no-hover'],
          'data-site-id' => $site_id,
          // Single Selects.
          'data-restrictions' => $restrictions ? 0 : 1,
          'data-available' => $available,
          'data-wheelchair' => $wheelchair ? 1 : 0,
          // Multi-selects.
          'data-language' => $language_keys ? implode('-', $language_keys) : implode('-', $this->vaxValues->settings('languages')),
          'data-remote-asl' => $language_text['remote_asl'],
          'data-access-mode' => implode('-', $access_mode_keys),
          'data-lat' => $location['lat'],
          'data-lng' => $location['lng'],
        ]),
        'last_updated' => date("F j, Y, g:i a", strtotime($last_updated)),
        'restrictions_text' => $restrictions_text,
        'location' => $location,
        'languages' => $language_text['printed_languages'],
        'eligibilities' => $eligibility_text,
        'access_modes' => $access_mode_text,
        'info_url' => $info_url,
        'available' => $available,
        'booking' => $booking,
        'brands' => $brands,
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
      '#alert' => $this->vaxValues->getAlert(),
      '#template_strings' => $this->vaxValues->settings('template_strings'),
      '#api_data' => $this->makeAPIData($this->allData),
      '#filters' => $this->makeFilters($this->allData),
      '#results' => $this->makeResults($this->allData),
    ];
  }

}
