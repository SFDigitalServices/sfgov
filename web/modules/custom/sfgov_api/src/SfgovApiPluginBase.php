<?php

namespace Drupal\sfgov_api;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Base class for sfgov_api plugins.
 */
abstract class SfgovApiPluginBase extends PluginBase implements SfgovApiInterface {

  protected $entity_type;

  public function getBundle() {
    return (string) $this->pluginDefinition['bundle'];
  }

  public function getEntityId() {
    return (string) $this->configuration['entity_id'] ?? $this->pluginDefinition['entity_id'];
  }

  abstract public function setBaseData($entity);

  abstract public function setCustomData($entity);

  abstract public function getEntities($entity_type, $bundle, $entity_id = NULL);

  public function prepareData() {
    $entities = $this->getEntities($this->entity_type, $this->getBundle(), $this->getEntityId());
    $data = [];
    foreach ($entities as $entity) {
      $base_data = $this->setBaseData($entity);
      $custom_data = $this->setCustomData($entity);
      $data[] = array_merge($base_data, $custom_data);
      if (count($data) > 5) {
        break;
      }
    }

    return $data;
  }

  public function sendJsonResponse() {
    return new JsonResponse($this->prepareData());
  }

  public function getReferencedData(array $entities, $reference_only = FALSE) {
    $entity_data = [];

    foreach ($entities as $entity) {
      $entity_type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $entity_data = [];

      if ($reference_only) {
        $entity_data[] = [
          'bundle' => $bundle,
          'id' => $entity->id(),
          'type' => $entity_type,
        ];
      }
      else {
        $sfgov_api_plugin_manager = \Drupal::service('plugin.manager.sfgov_api');
        $available_plugins = $sfgov_api_plugin_manager->getDefinitions();
        switch ($entity_type) {
        case 'paragraph':
          $plugin_label = 'paragraph_' . $bundle;
          break;
        case 'node':
          $plugin_label = 'node_' . $bundle;
          break;

        default:
          $entity_data[] = 'no data found';
          break;
        }

        if (in_array($plugin_label, array_keys($available_plugins))) {
          $plugin = $sfgov_api_plugin_manager->createInstance($plugin_label, []);
          $entity_data[] = $plugin->prepareData();
        } else {
          $entity_data[] = 'Error: no available plugins for this entity';
        }
      }
    }
    return $entity_data;
  }

}
