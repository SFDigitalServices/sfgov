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
   * Get config.
   */
  private function settings($value) {
    return $this->configFactory->get('sfgov_vaccine.settings')->get($value);
  }

  /**
   * Get the microservice url from config.
   */
  private function getAPIUrl() {
    return $this->settings('api_url');
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

    $error_message = $this->settings('error_message');

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
        $key = $this->settings('access_mode.' . $key . '.short_key');
        array_push($keys, $key);
      }
    }
    array_push($keys, 'all');

    return $keys;
  }

  /**
   * Prepare each site's data-eligibility value.
   */
  private function getSiteEligibilityKeys($site_data, $group, $extra) {
    $keys = [];
    $site_data_group = $site_data[$group];
    foreach ($site_data_group as $data_key => $boolean) {
      if ($boolean === TRUE && $data_key != 'info') {
        $short_key = $this->settings($group . '.' . $data_key . '.short_key');
        array_push($keys, $short_key);
      }
    }
    array_push($keys, $extra);

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
        $text = $this->settings('access_mode.' . $key . '.text');
        array_push($printed, $this->t($text));
      }
    }

    return $printed;
  }

  /**
   * Prepare each site's eligibility text.
   */
  private function getSiteEligibilityText($site_data, $group) {
    $printed = [];
    $site_data_group = $site_data[$group];
    foreach ($site_data_group as $data_key => $boolean) {
      $text = $this->settings($group . '.' . $data_key . '.text');
      if ($boolean === TRUE && $data_key != 'info' && isset($text)) {
        $printed_value = $this->t($text);
        array_push($printed, $printed_value);
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
      $language_label = $this->settings(sprintf('languages.%s.site_label', $short_key));
      if ($boolean === TRUE && !empty($language_label)) {
        array_push(
          $printed_languages, $language_label);
      }
    }

    if ($site_data_remote_translation['available']) {
      array_push($printed_languages, $site_data_remote_translation['info']);
    }
    return $printed_languages;
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

      $eligibility_keys = $this->getSiteEligibilityKeys($site_data, 'eligibility', 'all');
      $eligibility_text = $this->getSiteEligibilityText($site_data, 'eligibility');
      $language_keys = $this->getSiteLanguageKeys($site_data['access']);
      $language_text = $this->getSiteLanguageText($site_data['access']);
      $access_mode_keys = $this->getSiteAccessModeKeys($site_data);
      $access_mode_text = $this->getSiteAccessModeText($site_data);

      // Usable variables.
      $info_url = NULL;
      $booking_info = NULL;

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
          'data-language' => $language_keys ? implode('-', $language_keys) : implode('-', $this->settings('languages')),
          'data-access-mode' => implode('-', $access_mode_keys),
          'data-eligibility' => implode('-', $eligibility_keys),
        ]),
        'last_updated' => date("F j, Y, g:i a", strtotime($last_updated)),
        'restrictions' => $restrictions_text,
        'address_text' => $address_text,
        'address_url' => $address_url,
        'languages' => $language_text,
        'eligibilities' => $eligibility_text,
        'access_modes' => $access_mode_text,
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
      '#template_strings' => $this->settings('template_strings'),
      '#api_data' => $this->makeAPIData($this->allData),
      '#filters' => $this->makeFilters($this->allData),
      '#results' => $this->makeResults($this->allData),
    ];
  }

}
