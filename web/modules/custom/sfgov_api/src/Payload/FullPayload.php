<?php

namespace Drupal\sfgov_api\Payload;

/**
 * Class for Json Payloads to be sent to Wagtail.
 */
class FullPayload extends PayloadBase {

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
   * @param string $requestedLangcode
   *   The requested langcode.
   * @param array $pluginErrors
   *   The plugin errors.
   * @param string $wagBundle
   *   The wagtail bundle.
   * @param array $baseData
   *   The base data.
   * @param array $customData
   *   The custom data.
   *
   * @return \Drupal\sfgov_api\Payload\Payload
   * The Payload object.
   */
  public function __construct($entity, $requestedLangcode, $pluginErrors, $wagBundle, $baseData, $customData,) {
    parent::__construct($entity, $requestedLangcode, $pluginErrors, $wagBundle);
    $this->baseData = $baseData;
    $this->customData = $customData;
    $this->emptyReferences = [];
    $this->setEmptyReferences($this->customData);
    $this->payloadData = $this->setPayloadData();
  }

  /**
   * Get the empty references.
   */
  public function getEmptyReferences() {
    return $this->emptyReferences;
  }

  /**
   * Recursively collect all the empty references.
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

  /**
   * {@inheritDoc}
   */
  public function setPayloadData() {
    if ($this->errors) {
      $payload = [];
    }
    else {
      $this->metadata['empty_references'] = $this->emptyReferences;
      $payload = array_merge($this->baseData, $this->customData);
    }

    return $this->payloadData = $payload;
  }

}
