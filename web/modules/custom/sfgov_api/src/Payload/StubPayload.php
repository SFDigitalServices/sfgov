<?php

namespace Drupal\sfgov_api\Payload;

use Drupal\node\Entity\Node;

/**
 * Class for Json Payloads to be sent to Wagtail.
 */
class StubPayload extends PayloadBase {

  /**
   * The base data for the payload, comes from the entitytype's plugin.
   *
   * @var array
   */
  protected $baseData;

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
   *
   * @return \Drupal\sfgov_api\Payload\Payload
   * The Payload object.
   */
  public function __construct($entity, $requestedLangcode, $pluginErrors, $wagBundle, $baseData) {
    parent::__construct($entity, $requestedLangcode, $pluginErrors, $wagBundle);
    $this->baseData = $baseData;
    $this->payloadData = $this->setPayloadData();
  }

  /**
   * {@inheritDoc}
   */
  public function setPayloadData() {
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

    return $this->payloadData = $stub;
  }

}
