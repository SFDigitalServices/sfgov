<?php

namespace Drupal\sfgov_api\Payload;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;

/**
 * Class for Json Payloads to be sent to Wagtail.
 */
class Payload {

  use StringTranslationTrait;

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
  public function __construct(?EntityInterface $entity, $baseData, $customData, $requestedLangcode, $wagBundle, $pluginErrors) {
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
    }
    return $this->stub = $stub;
  }

  /**
   * Set the payload data.
   */
  public function setPayloadData() {
    if ($this->errors) {
      $payload = [];
    }
    else {
      $payload = array_merge($this->baseData, $this->customData);
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

}
