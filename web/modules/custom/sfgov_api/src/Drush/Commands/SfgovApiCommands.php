<?php

namespace Drupal\sfgov_api\Drush\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(ClientInterface $httpClient, SfgApiPluginManager $sfgApiPluginManager, ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $configFactory, ApiUtilities $apiUtilities, EntityTypeManagerInterface $entityTypeManager, Connection $database) {
    parent::__construct();
    $this->httpClient = $httpClient;
    $this->sfgApiPluginManager = $sfgApiPluginManager;
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $configFactory;
    $this->apiUtilities = $apiUtilities;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
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
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * Install all current tracking tables based on existing plugins.
   *
   * @command sfgov_api:install_tracking_tables
   * @aliases itt
   */
  public function installTrackingTables() {
    $database = $this->database;
    $plugins = $this->sfgApiPluginManager->getDefinitions();
    foreach ($plugins as $plugin) {
      // We need tables for everything except paragraphs.
      if (!str_starts_with($plugin['id'], 'paragraph')) {
        $table_name = 'dw_migration_' . $plugin['id'] . '_id_map';
        if (!$database->schema()->tableExists($table_name)) {
          $schema = $this->apiUtilities->buildTrackingTableSchema($plugin['id']);
          $this->output()->writeln('Creating table: ' . $table_name);
          $database->schema()->createTable($table_name, $schema[$table_name]);
        }
      }
    }
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
   * @option print: Print the output from the cURL command.
   * @option stub: Push stubs of the entities.
   * @option update: Update the entities.
   * @option references: Push stubs of the referenced entities.
   * @command sfgov_api:push_entity_by_bundle
   * @aliases peb
   */
  public function pushEntitiesByBundle($entity_type, $bundle, $langcode, $options = [
    'print' => FALSE,
    'stub' => FALSE,
    'update' => FALSE,
    'references' => FALSE,
  ]) {
    $bundle_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->accessCheck(FALSE)
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
   * @option print: Print the output from the cURL command.
   * @option stub: Push stubs of the entities.
   * @option update: Update the entities.
   * @option references: Push stubs of the referenced entities.
   * @command sfgov_api:push_entity
   * @aliases pe
   */
  public function pushEntity($entity_type, $bundle, $langcode, $entity_id, $options = [
    'print' => FALSE,
    'stub' => FALSE,
    'update' => FALSE,
    'references' => FALSE,
  ]) {
    // Get the correct plugin for the display.
    if (!$plugin_label = $this->sfgApiPluginManager->validatePlugin($entity_type, $bundle)) {
      return $this->output()->writeln('no matching plugin found with those arguments');
    }

    $node_exists = $this->apiUtilities->getWagtailId($entity_id, $entity_type, $bundle, $langcode);
    if ($node_exists && !$options['update']) {
      $options['update'] = TRUE;
      $message = $this->t('@entity_type of type @bundle with ID @entity_id in langcode @langcode already exists. Updating...', [
        '@entity_type' => $entity_type,
        '@bundle' => $bundle,
        '@entity_id' => $entity_id,
        '@langcode' => $langcode,
      ]);
      $this->output()->writeln($message);
    }

    $payload = $this->sfgApiPluginManager->fetchPayload($plugin_label, $langcode, $entity_id, $options['stub']);

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

    // If the references option is set, get the referenced entities and push
    // stubs of them first.
    if ($options['references']) {
      $referenced_entities = $payload->getEmptyReferences();
      foreach ($referenced_entities as $referenced_entity) {
        $reference_exists = $this->apiUtilities->getWagtailId($referenced_entity['entity_id'], $referenced_entity['entity_type'], $referenced_entity['bundle'], $referenced_entity['langcode']);
        if (!$reference_exists) {
          $this->pushEntity(
            $referenced_entity['entity_type'],
            $referenced_entity['bundle'],
            $referenced_entity['langcode'],
            $referenced_entity['entity_id'],
          [
            'print' => FALSE,
            'stub' => TRUE,
            'update' => FALSE,
            'references' => FALSE,
          ]);
        }
      }
      $message = $this->t('All references updated for @entity_type:@bundle with ID @entity_id in langcode @langcode updated.', [
        '@entity_type' => $entity_type,
        '@bundle' => $bundle,
        '@entity_id' => $entity_id,
        '@langcode' => $langcode,
      ]);
      return $this->output()->writeln($message);
    }
    else {
      return $this->pushToWagtail($payload, $bundle, $options);
    }
  }

  /**
   * Clear all content from the wagtail tables in Drupal.
   *
   * @param array $options
   *   Which table set to clear.
   *
   * @command sfgov_api:clear_wagtail_tables
   * @aliases cwt
   */
  public function clearWagtailTables($options = [
    'node' => FALSE,
    'media' => FALSE,
    'eck' => FALSE,
    'errors' => FALSE,
    'all' => FALSE,
  ]) {
    $plugins = $this->sfgApiPluginManager->getDefinitions();

    $tables = [];
    if ($options['node'] || $options['all']) {
      foreach ($plugins as $plugin) {
        if (str_starts_with($plugin['id'], 'node')) {
          $tables[] = 'dw_migration_' . $plugin['id'] . '_id_map';
        }
      }
    }
    if ($options['media'] || $options['all']) {
      foreach ($plugins as $plugin) {
        if (str_starts_with($plugin['id'], 'media')) {
          $tables[] = 'dw_migration_' . $plugin['id'] . '_id_map';
        }
      }
    }
    if ($options['eck'] || $options['all']) {
      foreach ($plugins as $plugin) {
        if (str_starts_with($plugin['id'], 'location') || str_starts_with($plugin['id'], 'resource')) {
          $tables[] = 'dw_migration_' . $plugin['id'] . '_id_map';
        }
      }
    }
    if ($options['errors'] || $options['all']) {
      $tables[] = 'dw_migration_errors';
    }
    foreach ($tables as $table_name) {
      $this->apiUtilities->clearWagtailTable($table_name);
      $this->output()->writeln('Cleared table: ' . $table_name);
    }
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

        // @todo , temporary fix to clear entity reference fields that aren't working.
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

      case 'media' || 'file':
        $api_url_complete = $this->apiUtilities->getCredentials()['api_url_base'] . $wag_bundle;
        $client_config['multipart'] = $this->apiUtilities->prepMultipart($payload->getPayloadData());
        break;

      // Special exceptions for Eck entities.
      case 'location':
        $api_url_complete = $this->apiUtilities->getCredentials()['api_url_base'] . 'cms.' . $wag_bundle;
        $client_config['json'] = $payload->getPayloadData();
        break;
    }

    // Conditionally add logger middleware if the 'print' option is enabled.
    if ($options['print']) {
      $client_config = $this->apiUtilities->printCurlCommand($client_config);
    }

    if ($options['stub'] && !empty($payload->getStubData())) {
      $client_config['query']['stub'] = TRUE;
      $client_config['json'] = $payload->getStubData();
    }

    if ($options['update']) {
      $update_id = $this->apiUtilities->getWagtailId($drupal_id, $entity_type, $bundle, $langcode);
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
        $url_elements = isset($return_data_array['detail_url']) ? parse_url($return_data_array['detail_url']) : parse_url($return_data_array['url']);
        $url_array = explode('/', trim($url_elements['path'], '/'));
        // For some reason the file bundle has a different URL structure.
        if ($bundle === 'file') {
          $wag_page_id = $url_array[1];
        }
        else {
          $wag_page_id = end($url_array);
        }
        $wag_page_status = $options['stub'] ? 'stub' : 'complete';
        $message = $this->t('Successfully pushed entity: @bundle:@drupal_id to Wagtail with ID: @wag_page_id', [
          '@bundle' => $bundle,
          '@drupal_id' => $drupal_id,
          '@wag_page_id' => $wag_page_id,
        ]);
      }
      catch (GuzzleException $error) {
        $response = $error->getResponse();
        $response_info = $response ? $response->getBody()->getContents() : $error->getMessage();
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
    $this->apiUtilities->updateWagIdTable($entity_type, $bundle, $drupal_id, $wag_page_status, $langcode, $wag_page_id);
    $this->output()->writeln($message);
  }

}
