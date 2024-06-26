<?php

namespace Drupal\sfgov_api;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sfgov_api\Payload\FullPayload;
use Drupal\sfgov_api\Payload\RawPayload;
use Drupal\sfgov_api\Payload\StubPayload;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgApiPluginBase extends PluginBase implements SfgApiInterface {

  use StringTranslationTrait;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The plugin errors.
   *
   * @var array
   */
  protected $pluginErrors = [];

  /**
   * The payload.
   *
   * @var \Drupal\sfgov_api\Payload\Payload
   */
  protected $payload;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs a new SfgApiPluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->checkTrackingTable();
    $this->entity = $this->setEntity();
    $this->payload = $this->setPayload();
  }

  /**
   * Get the payload value.
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * Get the entity value.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Get the bundle value.
   */
  public function getBundle() {
    return (string) $this->pluginDefinition['bundle'];
  }

  /**
   * Get the wagtail bundle value.
   */
  public function getWagBundle() {
    return (string) $this->pluginDefinition['wag_bundle'];
  }

  /**
   * Get the wagtail bundle value.
   */
  public function getReferencedPlugins() {
    return (string) $this->pluginDefinition['referenced_plugins'];
  }

  /**
   * Get the langcode value.
   */
  public function getLangcode() {
    return (string) $this->configuration['langcode'] ?? $this->pluginDefinition['langcode'];
  }

  /**
   * Get the entity_id value.
   */
  public function getEntityId() {
    return (string) $this->configuration['entity_id'] ?? $this->pluginDefinition['entity_id'];
  }

  /**
   * Get the shape value.
   */
  public function getShape() {
    return (string) $this->configuration['shape'] ?? $this->pluginDefinition['shape'];
  }

  /**
   * The emptyReference value.
   *
   * @var array
   */
  protected $emptyReference = [];

  /**
   * Get the emptyReference value.
   */
  public function getEmptyReferences() {
    return $this->emptyReference;
  }

  /**
   * Set BaseData for the prepareData function.
   */
  abstract public function setBaseData(EntityInterface $entity);

  /**
   * Set CustomData for the prepareData function.
   */
  abstract public function setCustomData(EntityInterface $entity);

  /**
   * Set the entity being processed and return errors if it doesn't exist.
   */
  public function setEntity() {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_storage = $entity_type_manager->getStorage($this->entityType);
    $entity = $entity_storage->load($this->getEntityId());
    $requested_langcode = $this->getLangcode();

    // Make sure the entity actually exists.
    if (!$entity) {
      $message = $this->t('No @entity_type of type @bundle with ID @id found.', [
        '@entity_type' => $this->entityType,
        '@bundle' => $this->getBundle(),
        '@id' => $this->getEntityId(),
      ]);
      $this->addPluginError('No entity', $message);
      $entity = NULL;
    }
    // Make sure the entity exists in that language.
    elseif (!$entity->hasTranslation($requested_langcode)) {
      $message = $this->t('No @entity_type of type @bundle with ID @id in langcode @langcode found.', [
        '@entity_type' => $entity->getEntityTypeId(),
        '@bundle' => $entity->bundle(),
        '@id' => $entity->id(),
        '@langcode' => $requested_langcode,
      ]);
      $this->addPluginError('No translation', $message);
      // Leave entity at the english version for error processing.
    }
    else {
      $entity = $entity->getTranslation($requested_langcode);
    }
    return $this->entity = $entity;
  }

  /**
   * Set the payload.
   */
  public function setPayload() {
    $requested_langcode = $this->getLangcode();
    $plugin_errors = $this->pluginErrors;
    $wag_bundle = $this->getWagBundle();
    $entity = $this->getEntity();
    if (!$entity) {
      return $this->payload = [];
    }

    switch ($this->getShape()) {
      case 'stub':
        $base_data = $this->setBaseData($entity);
        $payload = new StubPayload($entity, $requested_langcode, $plugin_errors, $wag_bundle, $base_data);
        break;

      case 'raw':
        $payload = new RawPayload($entity, $requested_langcode, $plugin_errors, $wag_bundle);
        break;

      case 'wag':
        $base_data = $this->setBaseData($entity);
        $custom_data = $this->setCustomData($entity);
        $payload = new FullPayload($entity, $requested_langcode, $plugin_errors, $wag_bundle, $base_data, $custom_data,);
        break;

      case 'mix':
        $base_data = $this->setBaseData($entity);
        $custom_data = $this->setCustomData($entity);
        $wag_payload = new FullPayload($entity, $requested_langcode, $plugin_errors, $wag_bundle, $base_data, $custom_data);
        $raw_payload = new RawPayload($entity, $requested_langcode, $plugin_errors, $wag_bundle);

        $wag_payload->alterPayload([
          'wag' => $wag_payload->getPayloadData(),
          'raw' => $raw_payload->getPayloadData(),
        ]);

        $payload = $wag_payload;
        break;
    }

    return $this->payload = $payload;
  }

  /**
   * Add a plugin error.
   *
   * @param string $type
   *   The type of error.
   * @param string $message
   *   The error message.
   */
  public function addPluginError(string $type, string $message) {
    $this->pluginErrors[] = [
      'type' => $type,
      'message' => $message,
    ];
  }

  /**
   * Verify that a plugin has a corresponding tracking table.
   */
  public function checkTrackingTable() {
    $plugin_id = $this->getPluginId();
    if (!str_starts_with($plugin_id, 'paragraph')) {
      $database = \Drupal::database();
      $table_name = 'dw_migration_' . $plugin_id . '_id_map';
      if (!$database->schema()->tableExists($table_name)) {
        $this->addPluginError('No tracking table', 'No tracking table found for ' . $plugin_id . '. Please run drush sfgov_api:install_tracking_tables to create all tables.');
      }
    }
  }

}
