<?php

namespace Drupal\sfgov_api\Payload;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Class for Json Payloads to be sent to Wagtail.
 */
class Payload {

  use StringTranslationTrait;
  use ApiFieldHelperTrait;

  /**
   * The metadata used to support the payload.
   *
   * @var array
   */
  protected $metadata;

  /**
   * The data needed to create a stub entity in Wagtail.
   *
   * @var array
   */
  protected $stub;

  /**
   * The complete payload data to be sent to Wagtail.
   *
   * @var array
   */
  protected $payloadData;

  /**
   * Any error accumulated during the payload creation.
   *
   * @var array
   */
  protected $errors;

  /**
   * The entity being processed.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The base data for the payload, comes from the entitytype's plugin.
   *
   * @var array
   */
  protected $baseData;

  /**
   * The custom data, comes from a bundle's plugin.
   *
   * @var array
   */
  protected $customData;

  /**
   * The requested langcode.
   *
   * @var string
   */
  protected $requestedLangcode;

  /**
   * The wagtail bundle.
   *
   * @var string
   */
  protected $wagBundle;

  /**
   * The plugin errors.
   *
   * @var array
   */
  protected $pluginErrors;

  /**
   * The empty references.
   *
   * @var array
   */
  protected $emptyReferences;

  /**
   * The shape of the data.
   *
   * @var string
   */
  protected $shape;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new Payload object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param array $baseData
   *   The base data.
   * @param array $customData
   *   The custom data.
   * @param string $requestedLangcode
   *   The requested langcode.
   * @param string $wagBundle
   *   The wagtail bundle.
   * @param array $pluginErrors
   *   The plugin errors.
   *
   * @return \Drupal\sfgov_api\Payload\Payload
   * The Payload object.
   */
  public function __construct(?EntityInterface $entity, $baseData, $customData, $requestedLangcode, $wagBundle, $pluginErrors, $shape) {
    $this->shape = $shape;
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
    $this->entity = $entity;
    $this->baseData = $baseData;
    $this->customData = $customData;
    $this->requestedLangcode = $requestedLangcode;
    $this->wagBundle = $wagBundle;
    $this->pluginErrors = $pluginErrors;
    $this->errors = $this->checkErrors();
    $this->metadata = $this->setMetadata();
    $this->stub = $this->setStub();
    $this->payloadData = $this->setPayloadData();
    $this->emptyReferences = [];
    $this->setEmptyReferences($this->customData);
  }

  /**
   * Get the metadata.
   */
  public function getMetadata() {
    return $this->metadata;
  }

  /**
   * Get the stub data.
   */
  public function getStubData() {
    return $this->stub;
  }

  /**
   * Get the errors.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Get the payload data.
   */
  public function getPayloadData() {
    return $this->payloadData;
  }

  /**
   * Get the empty references.
   */
  public function getEmptyReferences() {
    return $this->emptyReferences;
  }

  /**
   * Check for errors in the plugin and entity request.
   */
  public function checkErrors() {
    $errors = [];
    return $this->errors = array_merge($errors, $this->pluginErrors);
  }

  /**
   * Set the metadata.
   */
  public function setMetadata() {
    $metadata = [];
    $entity = $this->entity;
    if (!empty($entity)) {
      $metadata = [
        'drupal_id' => (int) $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'bundle' => $entity->bundle(),
        'langcode' => $this->requestedLangcode,
        'title' => $entity->label(),
        'translations' => array_keys($entity->getTranslationLanguages()),
        'wag_bundle' => $this->wagBundle,
        'empty_references' => $this->emptyReferences ?: [],
      ];
    }

    return $this->metadata = $metadata;
  }

  /**
   * Set the stub data.
   */
  public function setStub() {
    $stub = [];
    $entity = $this->entity;
    if (!empty($entity)) {
      if ($entity instanceof Node) {
        $stub = [
          'parent_id' => (int) $this->baseData['parent_id'],
          'title' => $this->baseData['title'],
          'slug' => $this->baseData['slug'],
          'wag_bundle' => $this->wagBundle,
        ];
      }
      if ($entity->bundle() == 'physical' || $entity->bundle() == 'event_address') {
        $stub = [
          'line1' => $this->baseData['line1'],
          'city' => $this->baseData['city'],
          'state' => $this->baseData['state'],
          'zip' => $this->baseData['zip'],
        ];
      }
    }
    return $this->stub = $stub;
  }

  /**
   * Set the payload data.
   */
  public function setPayloadData() {
    if ($this->shape == 'wag') {
      if ($this->errors) {
        $payload = [];
      }
      else {
        $payload = array_merge($this->baseData, $this->customData);
      }
    }
    elseif ($this->shape == 'raw') {
      $payload = $this->getRawData();
    }

    return $this->payloadData = $payload;
  }

  /**
   * Collect all the empty references.
   *
   * @param array $custom_data
   *   The custom data.
   */
  public function setEmptyReferences($custom_data) {
    $empty_references = [];
    foreach ($custom_data as $key => $value) {
      if (is_array($value)) {
        $empty_references[$key] = $this->setEmptyReferences($value);
      }
      elseif ($key == 'empty_reference') {
        $this->emptyReferences[] = $custom_data;
      }
    }
  }

  public function getRawData() {
    $raw_data = [];

    $metadata = $this->metadata;
    $fields = $this->entityFieldManager->getFieldDefinitions($metadata['entity_type'], $metadata['bundle']);
    $raw_data = [];
    foreach ($fields as $field_name => $field_definition) {
      // check if field_name starts with field_
      if (strpos($field_name, 'field_') !== 0) {
        continue;
      }
      $field_type = $field_definition->getType();
      $field_data = $this->entity->get($field_name);
      $raw_data[$field_name] = $this->getFieldData($field_type, $field_data);
    }
    if ($metadata['entity_type'] === 'node') {
      $raw_data['metadata'] = $metadata;
    }
    return $raw_data;
  }

  public function getFieldData($field_type, $field_data) {
    $data = [];
    // All of these fields can be fetched the same way.
    $simple = [
      'integer',
      'decimal',
      'text_long',
      'boolean',
      'string',
      'string_long' ,'email',
      'telephone',
      'datetime',
      'list_string'
    ];

    // data is stored differently depending on the field type.
    switch ($field_type) {
      case in_array($field_type, $simple):
        $data = $field_data->value;
        break;

      case 'link':
        $data = [
          'uri' => $field_data->uri,
          'title' => $field_data->title,
          'options' => $field_data->options,
        ];
        break;

      case 'entity_reference_revisions':
        $data = $this->getReferencedData($field_data->referencedEntities(), '', 'raw');
        break;

      case 'entity_reference':
        $data = $this->getReferencedEntitiesRaw($field_data->referencedEntities(), '', 'raw');
        break;

      case 'image':
        $data = [
          'alt' => $field_data->alt,
          'title' => $field_data->title,
          'width' => $field_data->width,
          'height' => $field_data->height,
        ];
        $file = $field_data->entity;
        if ($file) {
          $data['target_id'] = $file->id();
          $data['file'] = [
            'filename' => $file->getFilename(),
            'uri' => $file->getFileUri(),
            'fid' => $file->id(),
            'filesize' => $file->getSize(),
            'filemime' => $file->getMimeType(),
          ];
        }
        break;

      case 'address':
        $data = [
          'address_line1' => $field_data->address_line1,
          'address_line2' => $field_data->address_line2,
          'locality' => $field_data->locality,
          'administrative_area' => $field_data->administrative_area,
          'postal_code' => $field_data->postal_code,
          'country_code' => $field_data->country_code,
        ];
        break;

      case 'smartdate':
        // This is the one value that isn't being presented "Raw" since
        // the logic of how it operates is built into the UI.
        $data = $this->convertSmartDate($field_data->getValue()[0]);
        break;

    }
    return $data;
  }

  public function getReferencedEntitiesRaw($entities) {
    $data = [];
    foreach ($entities as $entity) {
      $data[] = [
        'entity_type' => $entity->getEntityTypeId(),
        'bundle' => $entity->bundle(),
        'langcode' => $entity->language()->getId(),
        'entity_id' => $entity->id(),
      ];
    }
    return $data;

  }

}
