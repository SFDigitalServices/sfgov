<?php

namespace Drupal\sfgov_api;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgovApiPluginBase extends PluginBase implements SfgovApiInterface {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Get the bundle value.
   */
  public function getBundle() {
    return (string) $this->pluginDefinition['bundle'];
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
   * Set BaseData for the prepareData function.
   */
  abstract public function setBaseData($entity);

  /**
   * Set CustomData for the prepareData function.
   */
  abstract public function setCustomData($entity);

  /**
   * Prepare the data for the API.
   *
   * @return array
   *   The prepared data.
   */
  public function prepareData() {
    $entities = $this->getEntities($this->entityType, $this->getBundle(), $this->getLangcode(), $this->getEntityId());
    $data = [];
    foreach ($entities as $entity) {
      $drupal_data = [
        'drupal_data' => [
          'drupal_id' => $entity->id(),
          'entity_type' => $this->entityType,
          'bundle' => $this->getBundle(),
        ],
      ];
      // Set in the corresponding entity base plugin.
      $base_data = $this->setBaseData($entity);
      // Set in the corresponding bundle plugin.
      $custom_data = $this->setCustomData($entity);
      $data[] = array_merge($drupal_data, $base_data, $custom_data);

      // @todo this limit is here because some queries end up being too big
      // figure out pagination or some other solution.
      if (count($data) > 20) {
        break;
      }
    }

    return $data;
  }

  /**
   * Send the data as a JsonResponse.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JsonResponse of the prepared data.
   */
  public function sendJsonResponse() {
    return new JsonResponse($this->prepareData());
  }

  /**
   * Get the entities requested by the query.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $langcode
   *   The langcode, defaults to 'en'.
   * @param string $entity_id
   *   The entity id (optional).
   *
   * @return array
   *   An array of entities.
   */
  public function getEntities($entity_type, $bundle, $langcode, $entity_id = NULL) {
    $entities = [];
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_storage = $entity_type_manager->getStorage($entity_type);
    $entity_id_key = $entity_type_manager->getDefinition($entity_type)->getKeys()['id'];
    $query = \Drupal::entityQuery($entity_type)
      ->condition('type', $bundle);

    // If an entity_id is passed, only return that entity.
    if ($entity_id) {
      $query->condition($entity_id_key, $entity_id);
    }

    $nids = $query->execute();
    $entities = $entity_storage->loadMultiple($nids);

    // If the langcode is not 'en', get the translation for each entity. Remove
    // any entities that do not have a translation.
    if ($langcode != 'en') {
      foreach ($entities as $key => $entity) {
        if ($entity->hasTranslation($langcode)) {
          $entities[$key] = $entity->getTranslation($langcode);
        }
        else {
          unset($entities[$key]);
        }
      }
    }
    return $entities;
  }

}
