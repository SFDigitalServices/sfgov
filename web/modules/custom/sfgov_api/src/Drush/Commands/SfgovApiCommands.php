<?php

namespace Drupal\sfgov_api\Drush\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginManager;
use Drush\Commands\DrushCommands;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
class SfgovApiCommands extends DrushCommands {

  use ApiFieldHelperTrait;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  const CONTENT_TYPES = [
    'step_by_step',
    'topic',
  ];

  /**
   * Constructs a SfgovApiCommands object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http client.
   * @param \Drupal\sfgov_api\SfgApiPluginManager $sfgApiPluginManager
   *   The sfgov_api plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ClientInterface $httpClient, SfgApiPluginManager $sfgApiPluginManager, ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $configFactory) {
    parent::__construct();
    $this->httpClient = $httpClient;
    $this->sfgApiPluginManager = $sfgApiPluginManager;
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $configFactory;
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
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * Set the credentials for the API.
   */
  private function setCredentials() {
    $api_config = $this->configFactory->getEditable('sfgov_api.settings');
    $this->credentials = [
      'username' => $api_config->get('username'),
      'password' => $api_config->get('password'),
      'host_ip' => $api_config->get('host_ip'),
      'port' => $api_config->get('port'),
    ];
  }

  /**
   * Set the base URL for the API.
   */
  private function setApiUrlBase() {
    $this->apiUrlBase = 'http://' . $this->credentials['host_ip'] . ':' . $this->credentials['port'] . '/api/cms/sf.';
  }

  /**
   * Push all entities of a bundle type to the API.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $langcode
   *   The language code.
   * @param array $options
   *   Other options.
   *
   * @option print Print the output from the cURL command.
   * @option format The format of the output, either full or stub.
   * @command sfgov_api:push_entity_by_bundle
   * @aliases peb
   */
  public function pushEntitiesByBundle($entity_type, $bundle, $langcode, $options = [
    'print' => FALSE,
    'stub' => FALSE,
    'update' => FALSE,
  ]) {
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $query = $entity_storage->getQuery()
      ->condition('type', $bundle);
    $entity_ids = $query->execute();
    foreach ($entity_ids as $entity_id) {
      $this->pushEntity($entity_type, $bundle, $langcode, $entity_id, $options);
    }
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
   * @param array $options
   *   Other options.
   *
   * @option print Print the output from the cURL command.
   * @option format The format of the output, either full or stub.
   * @command sfgov_api:push_entity
   * @aliases pe
   */
  public function pushEntity($entity_type, $bundle, $langcode, $entity_id, $options = [
    'print' => FALSE,
    'stub' => FALSE,
    'update' => FALSE,
  ]) {
    // Get the correct plugin for the display.
    if (!$plugin_label = $this->sfgApiPluginManager->validatePlugin($entity_type, $bundle)) {
      return $this->output()->writeln('no matching plugin found with those arguments');
    }

    $json_data_derived = $this->sfgApiPluginManager->fetchJsonData($plugin_label, $langcode, $entity_id);

    if ($options['stub']) {
      $final_data = [
        'title' => $json_data_derived[0]['title'],
        'slug' => $json_data_derived[0]['slug'],
      ];
    }
    else {
      $final_data = $json_data_derived[0];
    }

    return $this->pushToWagtail($final_data, $bundle, $options);
  }

  /**
   * Push an entity to the API and log cURL request details.
   *
   * @param string $json_payload
   *   The JSON payload.
   * @param string $bundle
   *   The bundle.
   * @param array $options
   *   Other options.
   */
  private function pushToWagtail($json_payload, $bundle, $options) {
    // Set some variables for the process.
    $module_path = $this->moduleHandler->getModule('sfgov_api')->getPath();
    $database = \Drupal::service('database');
    $langcode = $json_payload['drupal_data']['langcode'];
    $drupal_id = $json_payload['drupal_data']['drupal_id'];
    // Camelcase bundle name for the API.
    $bundle_cc = str_replace('_', '', ucwords($bundle, '_'));
    $api_url_complete = $this->apiUrlBase . $bundle_cc;
    $error = [];

    // Create the client configuration array with common settings.
    $client_config = [
      'handler' => HandlerStack::create(),
      'auth' => [
        $this->credentials['username'],
        $this->credentials['password'],
      ],
      'json' => $json_payload,
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ];

    if (isset($json_payload['error'])) {
      // Write the error to the drupal_wagtail_errors table.
      $error = [
        'type' => $json_payload['error']['type'],
        'message' => $json_payload['error']['message'],
      ];
      $message = $json_payload['error']['message'];
    }

    if ($options['update']) {
      // Specify the column based on the provided language code.
      $column_name = 'wagtail_id_' . $langcode;

      // Use the Drupal database API to query the table.
      $query = \Drupal::database()->select('drupal_wagtail_id_map', 'm');
      $query->fields('m', [$column_name]);
      $query->condition('drupal_id', $drupal_id);
      $result = $query->execute();

      // Get the first result if any.
      $update_id = $result->fetchAssoc();
      $api_url_complete .= '/' . $update_id['wagtail_id_' . $langcode];
    }

    // Conditionally add logger middleware if the 'print' option is enabled.
    if ($options['print']) {
      $logger = new Logger('guzzle');
      $logger->pushHandler(new StreamHandler($module_path . '/src/Drush/Errors/' . 'curl_request_' . time(), Logger::DEBUG));
      $client_config['handler']->push(
        Middleware::log(
          $logger,
          new MessageFormatter('{req_body}')
        )
      );
    }

    $client = new Client($client_config);

    if (!$error) {
      try {
        // Send the request.
        $request_type = $options['update'] ? 'PUT' : 'POST';
        $response = $client->request($request_type, $api_url_complete, [
          'auth' => [
            $this->credentials['username'],
            $this->credentials['password'],
          ],
          'json' => $json_payload,
          'headers' => [
            'Content-Type' => 'application/json',
          ],
        ]);

        // Gather any data provided by the response.
        $return_json = $response->getBody()->getContents();
        $return_data_array = json_decode($return_json, TRUE);

        // Parse the URL to get an ID.
        $url_elements = parse_url($return_data_array['detail_url']);
        $url_array = explode('/', trim($url_elements['path'], '/'));
        $page_id = end($url_array);
        $error['type'] = 'none';
        $message = 'Successfully pushed page: ' . $bundle . ':' . $page_id . ' to Wagtail';
      }
      catch (GuzzleException $error) {
        $response = $error->getResponse();
        $response_info = $response->getBody()->getContents();
        $message = 'Something went wrong and its not an API error, pass the --print option to see the full response.';

        // Error messages come in two flavors. A JSON string or an HTML page.
        if (is_string($response_info) && is_array(json_decode($response_info, TRUE))) {
          $message = $response_info;
          $error = [
            'type' => 'Wagtail API',
            'message' => $message,
          ];
        }
        // If the response is not JSON, it's HTML.
        else {
          $error = [
            'type' => 'Wagtail Site',
            'message' => 'Unknown Wagtail error, pass the --print option to see the full response',
          ];
          if ($options['print']) {
            {
            // Save the error to an HTML page.
            $filename = 'html_error_' . time() . '.html';
            $filepath = $module_path . '/src/Drush/Errors/' . $filename;
            file_put_contents($filepath, $response_info);
            $message = 'Something went wrong, check the error directory for an html file and curl command with the timecode ' . time();
            }
          }
        }
      }
    }

    // Output results to tables and console.
    $error_type = isset($page_id) ? 'none' : $error['type'];
    $error_skip = ['none', 'no translation'];
    if (!in_array($error_type, $error_skip)) {
      $query = $database->insert('drupal_wagtail_errors')
        ->fields([
          'drupal_id' => $drupal_id,
          'type' => $error_type,
          'langcode' => $langcode,
          'message' => $error['message'],
        ]);
      $result = $query->execute();
      $error_id = $result;
    }
    $database->upsert('drupal_wagtail_id_map')
      ->key('drupal_id')
      ->fields([
        'drupal_id' => $drupal_id,
        'wagtail_id_' . $langcode => $page_id ?? 'ERROR',
        'wagtail_id_' . $langcode . '_error' => $error_id ?? $error['type'],
      ])
      ->execute();

    $this->output()->writeln($message);
  }

}
