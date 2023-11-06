<?php

namespace Drupal\sfgov_api;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgApiPluginBase extends PluginBase implements SfgApiInterface {

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
   * Get the wagtail bundle value.
   */
  public function getWagBundle() {
    return (string) $this->pluginDefinition['wag_bundle'];
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
  public function renderEntities($entities) {
    $data = [];

    foreach ($entities as $entity) {
      $data[] = $this->renderEntity($entity);
      // If the page attempts to display too many entities it might not load.
      if (count($data) > 30) {
        break;
      }
    }

    return $data;
  }

  /**
   * Prepare an individual entity's data for the API.
   *
   * @param EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The prepared data.
   */
  public function renderEntity($entity) {
    $drupal_data = [
      'drupal_data' => [
        'drupal_id' => $entity->id(),
        'entity_type' => $this->entityType,
        'bundle' => $this->getBundle(),
        'langcode' => $this->getLangcode(),
        'translations' => array_keys($entity->getTranslationLanguages()),
      ],
    ];
    if ($entity->hasTranslation($this->configuration['langcode'])) {
      $entity = $entity->getTranslation($this->configuration['langcode']);
      // Set in the corresponding entity base plugin.
      $base_data = $this->setBaseData($entity);
      // Set in the corresponding bundle plugin.
      $custom_data = $this->setCustomData($entity);
    }
    else {
      $base_data = [
        'error' => [
          'type' => 'no translation',
          'message' => 'no translation found of ' . $entity->getType() . ':' . $entity->id() . ' in langcode ' . $this->configuration['langcode'],
        ],
      ];
      $custom_data = [];
    }
    return array_merge($drupal_data, $base_data, $custom_data);
  }

  /**
   * Send a list of entities.
   *
   * @return array
   *   The prepared data.
   */
  public function getEntitiesList() {
    return $this->getEntities($this->entityType, $this->getBundle(), $this->getEntityId());
  }

  /**
   * Get the entities requested by the query.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $entity_id
   *   The entity id (optional).
   *
   * @return array
   *   An array of entities.
   */
  public function getEntities($entity_type, $bundle, $entity_id = NULL) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_storage = $entity_type_manager->getStorage($entity_type);
    $entity_definition = $entity_type_manager->getDefinition($entity_type);
    $entity_id_key = $entity_definition->getKeys()['id'];
    $bundle_key = $entity_definition->getKeys()['bundle'];
    $query = \Drupal::entityQuery($entity_type)
      ->condition($bundle_key, $bundle);

    // If an entity_id is passed, only return that entity.
    if ($entity_id) {
      $query->condition($entity_id_key, $entity_id);
    }

    $ids = $query->execute();
    return $entity_storage->loadMultiple($ids);
  }

}
