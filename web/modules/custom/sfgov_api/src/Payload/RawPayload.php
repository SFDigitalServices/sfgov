<?php

namespace Drupal\sfgov_api\Payload;

use Drupal\sfgov_api\Plugin\SfgApi\ApiFieldHelperTrait;

/**
 * Class for JSON Payloads to be sent to Wagtail.
 */
class RawPayload extends PayloadBase {

  use ApiFieldHelperTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Mapping of field types to handler functions.
   *
   * @var array
   */
  protected $fieldTypeHandlers = [];

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
   */
  public function __construct($entity, $requestedLangcode, $pluginErrors, $wagBundle) {
    parent::__construct($entity, $requestedLangcode, $pluginErrors, $wagBundle);
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
    $this->initializeFieldTypeHandlers();
    $this->payloadData = $this->setPayloadData();
  }

  /**
   * Initializes the mapping of field types to handler functions.
   */
  protected function initializeFieldTypeHandlers() {
    return $this->fieldTypeHandlers = [
      'link' => [$this, 'handleLinkField'],
      'entity_reference_revisions' => [$this, 'handleEntityReferenceRevisionsField'],
      'entity_reference' => [$this, 'handleEntityReferenceField'],
      'image' => [$this, 'handleImageField'],
      'address' => [$this, 'handleAddressField'],
      'smartdate' => [$this, 'handleSmartDateField'],
    ];
  }

  /**
   * Sets the payload data.
   *
   * @return array
   *   The payload data.
   */
  public function setPayloadData() {
    $raw_data = [];

    $metadata = $this->metadata;
    $fields = $this->entityFieldManager->getFieldDefinitions($metadata['entity_type'], $metadata['bundle']);
    foreach ($fields as $field_name => $field_definition) {
      // Only proceed 'field_'. All of the relevant base field data is in the
      // metadata.
      if (strpos($field_name, 'field_') !== 0) {
        continue;
      }
      $field_type = $field_definition->getType();
      $field_data = $this->entity->get($field_name);

      if (isset($this->fieldTypeHandlers[$field_type])) {
        $handler = $this->fieldTypeHandlers[$field_type];
        $raw_data[$field_name] = call_user_func($handler, $field_data);
      }
      else {
        // Use handleSimpleField as the default handler.
        $raw_data[$field_name] = $this->handleSimpleField($field_data);
      }
    }

    if ($metadata['entity_type'] === 'node') {
      $raw_data['metadata'] = $metadata;
    }

    return $this->payloadData = $raw_data;
  }

  /**
   * Handles simple fields (integer, decimal, etc.).
   *
   * @param mixed $field_data
   *   The field data.
   *
   * @return mixed
   *   The processed data.
   */
  protected function handleSimpleField($field_data) {
    return $field_data->value;
  }

  /**
   * Handles link fields.
   *
   * @param mixed $field_data
   *   The field data.
   *
   * @return array
   *   The processed data.
   */
  protected function handleLinkField($field_data) {
    return [
      'uri' => $field_data->uri,
      'title' => $field_data->title,
      'options' => $field_data->options,
    ];
  }

  /**
   * Handles entity reference revisions fields.
   *
   * @param mixed $field_data
   *   The field data.
   *
   * @return array
   *   The processed data.
   */
  protected function handleEntityReferenceRevisionsField($field_data) {
    $data = [];
    if (!$field_data->isEmpty()) {
      $data = $this->getReferencedData($field_data->referencedEntities(), '', 'raw');
    }
    return $data;
  }

  /**
   * Handles entity reference fields.
   *
   * @param mixed $field_data
   *   The field data.
   *
   * @return array
   *   The processed data.
   */
  protected function handleEntityReferenceField($field_data) {
    $data = [];
    if (!$field_data->isEmpty()) {
      foreach ($field_data->referencedEntities() as $entity) {
        $data[] = [
          'entity_type' => $entity->getEntityTypeId(),
          'bundle' => $entity->bundle(),
          'langcode' => $entity->language()->getId(),
          'entity_id' => $entity->id(),
        ];
      }
    }
    return $data;
  }

  /**
   * Handles image fields.
   *
   * @param mixed $field_data
   *   The field data.
   *
   * @return array
   *   The processed data.
   */
  protected function handleImageField($field_data) {
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
    return $data;
  }

  /**
   * Handles address fields.
   *
   * @param mixed $field_data
   *   The field data.
   *
   * @return array
   *   The processed data.
   */
  protected function handleAddressField($field_data) {
    return [
      'address_line1' => $field_data->address_line1,
      'address_line2' => $field_data->address_line2,
      'locality' => $field_data->locality,
      'administrative_area' => $field_data->administrative_area,
      'postal_code' => $field_data->postal_code,
      'country_code' => $field_data->country_code,
    ];
  }

  /**
   * Handles smartdate fields.
   *
   * @param mixed $field_data
   *   The field data.
   *
   * @return mixed
   *   The processed data.
   */
  protected function handleSmartDateField($field_data) {
    // Note: This is the one value that isn't being presented "Raw" since the
    // logic of how it operates is built into the Drupal UI.
    return $this->convertSmartDate($field_data->getValue()[0]);
  }

}
