<?php

namespace Drupal\sfgov_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Service description.
 */
class ApiUtilities {

  /**
   * The plugin.manager.sfgov_api service.
   *
   * @var \Drupal\sfgov_api\SfgApiPluginManager
   */
  protected $sfgovApiPluginManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The credentials for getting into the API.
   *
   * @var array
   */
  protected $credentials;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an ApiUtilities object.
   *
   * @param \Drupal\sfgov_api\SfgApiPluginManager $sfgovApiPluginManager
   *   The plugin.manager.sfgov_api service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(SfgApiPluginManager $sfgovApiPluginManager, ConfigFactoryInterface $configFactory, Connection $connection, ModuleHandlerInterface $moduleHandler) {
    $this->sfgovApiPluginManager = $sfgovApiPluginManager;
    $this->configFactory = $configFactory;
    $this->connection = $connection;
    $this->moduleHandler = $moduleHandler;
    $this->credentials = $this->setCredentials();
  }

  /**
   * Get the Wagtail ID of a Drupal entity.
   *
   * @param int $drupal_id
   *   The Drupal ID of the entity.
   * @param string $entity_type
   *   The entity type.
   * @param string $langcode
   *   The language code of the entity.
   */
  public function getWagtailId($drupal_id, $entity_type, $langcode) {
    // Specify the column based on the provided language code.
    $column_name = 'wagtail_id_' . $langcode;

    // Use the Drupal database API to query the table.
    $table_name = 'drupal_wagtail_' . $entity_type . '_id_map';
    $query = $this->connection->select($table_name, 'm');
    $query->fields('m', [$column_name]);
    $query->condition('drupal_id', $drupal_id);
    $result = $query->execute()->fetchAssoc();
    $id = $result['wagtail_id_' . $langcode] ?? NULL;
    return $id;
  }

  /**
   * Get the Wagtail bundle for a Drupal entity.
   */
  public function getWagBundle(EntityInterface $entity) {
    // @todo this instantiates the whole plugin, which is not necessary.
    // Find a less expensive way to do this.
    if ($plugin_label = $this->sfgovApiPluginManager->validatePlugin($entity->getEntityTypeId(), $entity->bundle())) {
      $plugin = $this->sfgovApiPluginManager->createInstance($plugin_label, [
        'langcode' => $entity->language()->getId(),
        'entity_id' => $entity->id(),
      ]);
      return $plugin->getWagBundle();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Set the credentials for the API.
   */
  public function setCredentials() {
    $api_config = $this->configFactory->getEditable('sfgov_api.settings');
    return [
      'username' => $api_config->get('username'),
      'password' => $api_config->get('password'),
      'api_url_base' => $api_config->get('api_url_base'),
    ];
  }

  /**
   * Get the credentials for the API.
   */
  public function getCredentials() {
    return $this->credentials;
  }

  /**
   * Build the Wagtail client config.
   *
   * @return array
   *   The client config.
   */
  public function buildWagClientConfig() {
    $client_config = [
      'handler' => HandlerStack::create(),
      'auth' => [
        $this->credentials['username'],
        $this->credentials['password'],
      ],
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ];

    return $client_config;
  }

  /**
   * Prepare the payload for a multipart request (used for file uploads).
   *
   * @param array $payload_data
   *   The payload.
   */
  public function prepMultipart($payload_data) {
    $multipart = [];
    foreach ($payload_data as $key => $value) {
      if (is_array($value)) {
        continue;
      }
      if ($key === 'file' && file_exists($value)) {
        $multipart[] = [
          'name' => 'file',
          'contents' => fopen($value, 'r'),
        ];
        continue;
      }
      $multipart[] = [
        'name' => $key,
        'contents' => $value,
      ];
    }
    return $multipart;
  }

  /**
   * Update the Wagtail error table.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $drupal_id
   *   The Drupal ID of the entity.
   * @param string $error_type
   *   The type of error.
   * @param string $error_message
   *   The error message.
   * @param string $langcode
   *   The language code of the entity.
   *
   * @return int
   *   The ID of the inserted row and error record.
   */
  public function updateWagErrorTable(string $entity_type, int $drupal_id, string $error_type, string $error_message, string $langcode) {
    $query = $this->connection->insert('drupal_wagtail_errors')
      ->fields([
        'drupal_id' => $drupal_id,
        'entity_type' => $entity_type,
        'error_type' => $error_type,
        'langcode' => $langcode,
        'message' => $error_message,
      ]);
    $result = $query->execute();
    return $result;
  }

  /**
   * Update the Wagtail ID table.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $drupal_id
   *   The Drupal ID of the entity.
   * @param string $wag_page_status
   *   The status of the Wagtail page (stub, completed, error)
   * @param string $langcode
   *   The language code of the entity.
   * @param string $wag_page_id
   *   The ID of the corresponding Wagtail page.
   */
  public function updateWagIdTable(string $entity_type, int $drupal_id, string $wag_page_status, string $langcode, string $wag_page_id) {
    // If there is already a wag page ID, use that.
    if ($wag_page_id === 'none') {
      $wag_page_id = $this->getWagtailId($drupal_id, $entity_type, $langcode);
    }
    $table_name = 'drupal_wagtail_' . $entity_type . '_id_map';
    $this->connection->upsert($table_name)
      ->key('drupal_id')
      ->fields([
        'drupal_id' => $drupal_id,
        'wagtail_id_' . $langcode => $wag_page_id,
        'wagtail_id_' . $langcode . '_status' => $wag_page_status,
      ])
      ->execute();
  }

  /**
   * Add to the client config to print the curl command.
   *
   * @param array $client_config
   *   The client config.
   *
   * @return array
   *   The client config with the curl command.
   */
  public function printCurlCommand(array $client_config) {
    $module_path = $this->moduleHandler->getModule('sfgov_api')->getPath();
    $logger = new Logger('guzzle');
    $logger->pushHandler(new StreamHandler($module_path . '/src/Drush/Errors/' . 'curl_request_' . time(), Logger::DEBUG));
    $client_config['handler']->push(
      Middleware::log(
        $logger,
        new MessageFormatter('{req_body}')
      )
    );

    return $client_config;
  }

  /**
   * Clear all references from tables that Drupal is using to track migration.
   */
  public function clearWagtailTable($table_name) {
    $this->connection->truncate($table_name)->execute();
  }

}
