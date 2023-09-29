<?php

namespace Drupal\sfgov_api;

/**
 * Interface for sfgov_api plugins.
 */
interface SfgovApiInterface {

  /**
   * Get the bundle value.
   */
  public function getBundle();

  /**
   * Get the langcode value.
   */
  public function getLangcode();

  /**
   * Get the entity_id value.
   */
  public function getEntityId();

  /**
   * Set BaseData for the prepareData function.
   */
  public function setBaseData($entity);

  /**
   * Set CustomData for the prepareData function.
   */
  public function setCustomData($entity);

  /**
   * Prepare the data for the API.
   *
   * @return array
   *   The prepared data.
   */
  public function prepareData(array $entities);

  /**
   * Send the JSON response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function sendJsonResponse();

  /**
   * Get the entities.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $langcode
   *   The langcode.
   * @param string $entity_id
   *   The entity id.
   *
   * @return array
   *   The entities.
   */
  public function getEntities($entity_type, $bundle, $langcode, $entity_id);

}
