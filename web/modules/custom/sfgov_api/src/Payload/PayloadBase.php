<?php

namespace Drupal\sfgov_api\Payload;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class for Json Payloads to be sent to Wagtail.
 */
abstract class PayloadBase {

  use StringTranslationTrait;

  /**
   * The metadata used to support the payload.
   *
   * @var array
   */
  protected $metadata;

  /**
   * The wagtail bundle.
   *
   * @var string
   */
  protected $wagBundle;

  /**
   * The complete payload data to be converted to JSON.
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
   * The requested langcode.
   *
   * @var string
   */
  protected $requestedLangcode;

  /**
   * The plugin errors.
   *
   * @var array
   */
  protected $pluginErrors;

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
   * @param string $requestedLangcode
   *   The requested langcode.
   * @param array $pluginErrors
   *   The plugin errors.
   * @param string $wagBundle
   *   The wagtail bundle.
   *
   * @return \Drupal\sfgov_api\Payload\Payload
   * The Payload object.
   */
  public function __construct(?EntityInterface $entity, $requestedLangcode, $pluginErrors, $wagBundle) {
    $this->entity = $entity;
    $this->requestedLangcode = $requestedLangcode;
    $this->wagBundle = $wagBundle;
    $this->pluginErrors = $pluginErrors;
    $this->errors = $this->checkErrors();
    $this->metadata = $this->setMetadata();
  }

  /**
   * Set the payload data.
   */
  abstract public function setPayloadData();

  /**
   * Get the metadata.
   */
  public function getMetadata() {
    return $this->metadata;
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
        'published' => $entity->isPublished(),
      ];
    }

    return $this->metadata = $metadata;
  }

  public function alterPayload($payload) {
    return $this->payloadData = $payload;
  }

}
