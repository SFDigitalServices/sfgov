<?php

namespace Drupal\sfgov_api\Drush\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sfgov_api\ApiUtilities;
use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;
use Drupal\sfgov_api\SfgApiPluginManager;
use Drush\Commands\DrushCommands;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
class SfgovApiCommands extends DrushCommands {

  use ApiFieldHelperTrait;
  use StringTranslationTrait;

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

  /**
   * The API utilities service.
   *
   * @var \Drupal\sfgov_api\ApiUtilities
   */
  protected $apiUtilities;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\sfgov_api\ApiUtilities $apiUtilities
   *   The API utilities service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(ClientInterface $httpClient, SfgApiPluginManager $sfgApiPluginManager, ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $configFactory, ApiUtilities $apiUtilities, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
    $this->httpClient = $httpClient;
    $this->sfgApiPluginManager = $sfgApiPluginManager;
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $configFactory;
    $this->apiUtilities = $apiUtilities;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('plugin.manager.sfgov_api'),
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('sfgov_api.utilities'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Push all entities of a bundle type to Wagtail.
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
    $bundle_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->condition($bundle_key, $bundle);
    $entity_ids = $query->execute();
    foreach ($entity_ids as $entity_id) {
      $this->pushEntity($entity_type, $bundle, $langcode, $entity_id, $options);
    }
  }

  /**
   * Push an entity to Wagtail.
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

    $payload = $this->sfgApiPluginManager->fetchPayload($plugin_label, $langcode, $entity_id);

    if (empty($payload->getPayloadData())) {
      // Try to send an error message from the payload since it will be more
      // specific. If that doesn't work, send a generic message.
      if ($payload_error = $payload->getErrors()) {
        $message = $payload_error[0]['message'];
      }
      else {
        $message = $this->t('No @entity_type of type @bundle with ID @entity_id in langcode @langcode found.', [
          '@entity_type' => $entity_type,
          '@bundle' => $bundle,
          '@entity_id' => $entity_id,
          '@langcode' => $langcode,
        ]);
      }
      return $this->output()->writeln($message);
    }

    return $this->pushToWagtail($payload, $bundle, $options);
  }

  /**
   * Push an entity to the API and log cURL request details.
   *
   * @param Drupal\sfgov_api\Payload\Payload $payload
   *   The payload.
   * @param string $bundle
   *   The bundle.
   * @param array $options
   *   Other options.
   */
  private function pushToWagtail($payload, $bundle, $options) {
    // Set some variables for the process.
    $payload_metadata = $payload->getMetadata();
    $langcode = $payload_metadata['langcode'];
    $drupal_id = $payload_metadata['drupal_id'];
    $entity_type = $payload_metadata['entity_type'];
    $wag_bundle = $payload_metadata['wag_bundle'];
    // Naming this variable $error_data so that it doesn't clash with the
    // GuzzleException $error variable.
    $payload_errors = $payload->getErrors();
    $wag_page_status = '';

    // Create the client configuration array with common settings.
    $client_config = $this->apiUtilities->buildWagClientConfig();

    switch ($entity_type) {
      case 'node':
        $api_url_complete = $this->apiUtilities->getCredentials()['api_url_base'] . 'sf.' . $wag_bundle;
        $payload_data = $payload->getPayloadData();

        // TEMPORARY fix to clear entity reference fields that aren't working.
        if (isset($payload_data['related_content_topics'])) {
          $payload_data['related_content_topics'] = [];
        }
        if (isset($payload_data["steps"])) {
          foreach ($payload_data["steps"] as $index => $step) {
            unset($payload_data['steps'][$index]["value"]["related_content_transactions"]);
          }
        }

        $client_config['json'] = $payload_data;
        break;

      case 'media':
        $api_url_complete = $this->apiUtilities->getCredentials()['api_url_base'] . $wag_bundle;
        $client_config['multipart'] = $this->apiUtilities->prepMultipart($payload->getPayloadData());
        break;
    }

    // Conditionally add logger middleware if the 'print' option is enabled.
    if ($options['print']) {
      $client_config = $this->apiUtilities->printCurlCommand($client_config);
    }

    if ($options['stub']) {
      $client_config['query']['stub'] = TRUE;
      $client_config['json'] = $payload->getStubData();
    }

    if ($options['update']) {
      $update_id = $this->apiUtilities->getWagtailId($drupal_id, $entity_type, $langcode);
      $api_url_complete .= '/' . $update_id;
    }

    if (!empty($payload_errors)) {
      // Write the error to the drupal_wagtail_errors table.
      foreach ($payload_errors as $error) {
        $error_id = $this->apiUtilities->updateWagErrorTable($entity_type, $drupal_id, $error['type'], $error['message'], $langcode);
      }
    }
    else {
      $client = new Client($client_config);
      try {
        // Send the request.
        $request_type = $options['update'] ? 'PUT' : 'POST';
        $response = $client->request($request_type, $api_url_complete);

        // Gather any data provided by the response.
        $return_json = $response->getBody()->getContents();
        $return_data_array = json_decode($return_json, TRUE);

        // Parse the URL to get an ID.
        $url_elements = parse_url($return_data_array['detail_url']);
        $url_array = explode('/', trim($url_elements['path'], '/'));
        $wag_page_id = end($url_array);
        $wag_page_status = $options['stub'] ? 'stub' : 'complete';
        $message = $this->t('Successfully pushed entity: @bundle:@drupal_id to Wagtail with ID: @wag_page_id', [
          '@bundle' => $bundle,
          '@drupal_id' => $drupal_id,
          '@wag_page_id' => $wag_page_id,
        ]);
      }
      catch (GuzzleException $error) {
        $response = $error->getResponse();
        $response_info = $response->getBody()->getContents();
        $wag_page_status = 'error';
        $message = 'Something went wrong and its not an API error, pass the --print option to see the full response.';

        // Error messages come in two flavors. A JSON string or an HTML page.
        if (is_string($response_info) && is_array(json_decode($response_info, TRUE))) {
          $message = $response_info;
          $curl_error = [
            'type' => 'Wagtail API',
            'message' => $message,
          ];
        }
        // If the response is not JSON, it's HTML.
        else {
          $curl_error = [
            'type' => 'Wagtail Site',
            'message' => 'Unknown Wagtail error, pass the --print option to see the full response',
          ];
          if ($options['print']) {
            {
            // Save the error to an HTML page.
            $wag_id = $wag_page_id ?: 'xx';
            $filename = 'error_' . $drupal_id . '-' . $wag_id . '.html';
            $filepath = $this->moduleHandler->getModule('sfgov_api')->getPath() . '/src/Drush/Errors/' . $filename;
            file_put_contents($filepath, $response_info);
            $message = 'Something went wrong, check the error directory for an html file and curl command with the timecode ' . time();
            }
          }
        }
      }
    }

    if (isset($curl_error)) {
      $error_id = $this->apiUtilities->updateWagErrorTable($entity_type, $drupal_id, $curl_error['type'], $curl_error['message'], $langcode);
      $wag_page_status = 'error (' . $error_id . ')';
      $wag_page_id = 'none';
    }
    $this->apiUtilities->updateWagIdTable($entity_type, $drupal_id, $wag_page_status, $langcode, $wag_page_id);
    $this->output()->writeln($message);
  }

}
