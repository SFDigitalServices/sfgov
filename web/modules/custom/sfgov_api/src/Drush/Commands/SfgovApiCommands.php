<?php

namespace Drupal\sfgov_api\Drush\Commands;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sfgov_api\SfgApiPluginManager;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
class SfgovApiCommands extends DrushCommands {

  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The sfgov_api plugin manager.
   *
   * @var \Drupal\sfgov_api\SfgApiPluginManager
   */
  protected $sfgApiPluginManager;

  /**
   * The credentials for getting into the API.
   *
   * @var array
   */
  protected $credentials;

  /**
   * The base URL for the API.
   *
   * @var string
   */
  protected $apiUrlBase;

  /**
   * Constructs a SfgovApiCommands object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http client.
   * @param \Drupal\sfgov_api\SfgApiPluginManager $sfgApiPluginManager
   *   The sfgov_api plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(ClientInterface $httpClient, SfgApiPluginManager $sfgApiPluginManager, ModuleHandlerInterface $moduleHandler) {
    parent::__construct();
    $this->httpClient = $httpClient;
    $this->sfgApiPluginManager = $sfgApiPluginManager;
    $this->moduleHandler = $moduleHandler;
    $this->setCredentials();
    $this->setApiUrlBase();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('plugin.manager.sfgov_api'),
      $container->get('module_handler')
    );
  }

  /**
   * Set the credentials for the API.
   */
  private function setCredentials() {
    $this->credentials = [
      'username' => 'admin',
      'password' => 'admin',
      'host_ip' => 'host.docker.internal',
      'port' => 8000,
    ];
  }

  /**
   * Set the base URL for the API.
   */
  private function setApiUrlBase() {
    $this->apiUrlBase = "http://" . $this->credentials['host_ip'] . ':' . $this->credentials['port'] . "/api/cms/sf.";
  }

  /**
   * Push an entity to the API using literal data. For testing purposes only.
   *
   * @command sfgov_api:push_literal
   * @aliases pl
   */
  public function pushLiteralData() {
    $bundle = 'news';
    $json_data_literal = [
      // Required metadata fields.
      'title' => 'API created record',
      'slug' => $this->generateRandomSlug(),
      'parent_id' => 2,
      // Individual fields by page.
      'headline' => 'Hello',
      'date' => "2023-07-10",
      'abstract' => 'news',
      'news_type' => 'news',
    ];
    $this->pushToWagtail($json_data_literal, $bundle);
  }

  /**
   * Push an entity to the API.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $langcode
   *   The language code.
   * @param string $entity_id
   *   The entity id.
   *
   * @command sfgov_api:push_by_id
   * @aliases pbid
   */
  public function pushEntityById($entity_type, $bundle, $langcode, $entity_id) {

    $available_plugins = $this->sfgApiPluginManager->getDefinitions();
    $plugin_label = "{$entity_type}_{$bundle}";

    // Get the correct plugin for the display.
    if (in_array($plugin_label, array_keys($available_plugins))) {
      $plugin = $this->sfgApiPluginManager->createInstance($plugin_label, [
        'langcode' => $langcode,
        'entity_id' => $entity_id,
      ]);
      $entities = $plugin->getEntitiesList();
      $prepared_data = $plugin->renderEntities($entities);
      $json_data_derived = $prepared_data->fetchJsonData($plugin_label, $langcode, $entity_id);
      $this->pushToWagtail($json_data_derived, $bundle);
    }
    else {
      $this->output()->writeln('no matching plugin found with those arguments');
    }
  }

  /**
   * Push an entity to the API.
   *
   * @param string $json_payload
   *   The json payload.
   * @param string $bundle
   *   The bundle.
   */
  private function pushToWagtail($json_payload, $bundle) {
    // Camelcase bundle name for the api.
    $bundle_cc = str_replace('_', '', ucwords($bundle, '_'));

    $api_url_complete = $this->apiUrlBase . $bundle_cc;
    $client = $this->httpClient;
    try {
      // Send the request.
      $response = $client->request('POST', $api_url_complete, [
        'auth' => [
          $this->credentials['username'],
          $this->credentials['password'],
        ],
        'json' => $json_payload,
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);

      // Get any data provided by the response. The shape will match the API
      // fields at https://api.staging.dev.sf.gov/api/cms/sf.{BundleName}
      $return_json = $response->getBody()->getContents();
      $return_data_array = json_decode($return_json, TRUE);

      // Parse the URL to get an id. There will probably be other data we need
      // to preserve.
      $url_elements = parse_url($return_data_array['detail_url']);
      $url_array = explode('/', trim($url_elements['path'], '/'));
      $page_id = end($url_array);
      $this->writeln('Successfully pushed page: ' . $bundle . ':' . $page_id);
    }
    catch (GuzzleException $error) {
      $response = $error->getResponse();
      $response_info = $response->getBody()->getContents();

      // Error messages come in two flavors. A json string or an html page.
      if (is_string($response_info) && is_array(json_decode($response_info, TRUE))) {
        $message = $response_info;
      }
      else {
        // Save the error to an html page.
        $filename = 'response_' . time() . '.html';
        $module_path = $this->moduleHandler->getModule('sfgov_api')->getPath();
        $filepath = $module_path . '/src/Drush/Errors/' . $filename;
        file_put_contents($filepath, $response_info);
        $message = 'something went wrong, check the Error directory for the response: ' . $filename;
      }

      // Display the link.
      $this->output()->writeln($message);
    }
  }

  /**
   * Generate a random slug for testing (delete this later).
   *
   * @param int $length
   *   The length of the slug.
   *
   * @return string
   *   The random slug.
   */
  public function generateRandomSlug($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_slug = '';

    for ($i = 0; $i < $length; $i++) {
      $random_slug .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $random_slug;
  }

}
