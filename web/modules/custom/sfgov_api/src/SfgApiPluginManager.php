<?php

namespace Drupal\sfgov_api;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\field\Entity\FieldConfig;

/**
 * SfgApi plugin manager.
 */
class SfgApiPluginManager extends DefaultPluginManager {

  /**
   * Constructs SfgApiPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/SfgApi',
      $namespaces,
      $module_handler,
      'Drupal\sfgov_api\SfgApiInterface',
      'Drupal\sfgov_api\Annotation\SfgApi'
    );
    $this->alterInfo('sfgov_api_info');
    $this->setCacheBackend($cache_backend, 'sfgov_api_plugins');
  }

  /**
   * Fetch the payload from the plugin.
   *
   * @param string $plugin_label
   *   The plugin label.
   * @param string $langcode
   *   The language code.
   * @param int $entity_id
   *   The entity id.
   * @param bool $is_stub
   *   Whether the entity is a stub.
   */
  public function fetchPayload($shape, $plugin_label, $langcode, $entity_id, $is_stub = FALSE) {
    $plugin = $this->createInstance($plugin_label, [
      'langcode' => $langcode,
      'entity_id' => $entity_id,
      'is_stub' => $is_stub,
      'shape' => $shape,
    ]);
    $payload = $plugin->getPayload();
    return $payload;
  }

  /**
   * Validate that the plugin exists.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return string|bool
   *   The plugin label or FALSE.
   */
  public function validatePlugin($entity_type, $bundle) {
    $available_plugins = $this->getDefinitions();
    $plugin_label = "{$entity_type}_{$bundle}";

    if (in_array($plugin_label, array_keys($available_plugins))) {
      return $plugin_label;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get the raw reference chain for an entity type and bundle.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   The raw reference chain.
   */
  public function getRawReferenceChain($entity_type, $bundle) {
    $reference_fields = $this->getReferenceFields($entity_type, $bundle);
    $chain = [];
    foreach ($reference_fields as $field_name => $field_definition) {
      $targets = $field_definition->getSetting('handler_settings')['target_bundles'];
      $target_type = $field_definition->getSetting('target_type');
      foreach ($targets as $target) {
        $key = $target_type . '_' . $target;
        $final_entities = ['node', 'media', 'location'];
        if (in_array($target_type, $final_entities)) {
          $chain[$field_name][] = $key;
        }
        else {
          $reference_fields = $this->getRawReferenceChain($target_type, $target);
          if (!empty($reference_fields)) {
            $chain[$field_name][$key] = $reference_fields;
          }
          else {
            $chain[$field_name][$key] = [];
          }
        }
      }
    }
    return $chain;
  }

  /**
   * Get the list of plugins in the reference chain.
   *
   * @param array $raw_reference_chain
   *   The raw reference chain.
   *
   * @return array
   *   The list of plugins.
   */
  public function getReferenceChainPluginList($raw_reference_chain) {
    $plugin_list = [];
    foreach ($raw_reference_chain as $value) {
      if (is_array($value)) {
        $plugin_list = array_merge($plugin_list, $this->getReferenceChainPluginList($value));
      }
      else {
        $plugin_list[] = $value;
      }
    }
    return array_unique($plugin_list);
  }

  /**
   * Get the reference fields for an entity type and bundle.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   The reference fields.
   */
  public function getReferenceFields($entity_type, $bundle) {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
    $reference_fields = [];
    foreach ($fields as $field_name => $field_definition) {
      if ($field_definition instanceof FieldConfig) {
        $type = $field_definition->getType();
        if ($type === 'entity_reference' || $type === 'entity_reference_revisions') {
          $reference_fields[$field_name] = $field_definition;
        }
      }
    }
    return $reference_fields;
  }

  /**
   * Get the full reference chain for all plugins.
   */
  public function referenceChainComplete() {
    $plugins = $this->getDefinitions();
    $plugin_labels = [];
    foreach ($plugins as $plugin) {
      if (str_starts_with($plugin['id'], 'node')) {
        $plugin_labels[] = $plugin['id'];
      }
    }

    $reference_chains = [];
    foreach ($plugin_labels as $plugin_label) {
      $reference_chain = $this->referenceChainDown($plugin_label);
      $reference_chains[$plugin_label] = $reference_chain;
    }
    return $reference_chains;
  }

  /**
   * Get the plugins that this plugin references.
   *
   * @param string $plugin_label
   *   The plugin being searched for.
   */
  public function referenceChainDown($plugin_label) {
    $label_values = explode('_', $plugin_label, 2);
    $reference_chain = $this->getRawReferenceChain($label_values[0], $label_values[1]);
    return $reference_chain;
  }

  /**
   * Get the plugins that reference this plugin.
   *
   * @param string $plugin_label
   *   The plugin being searched for.
   *
   * @return array
   *   The reference chain.
   */
  public function referenceChainUp($plugin_label) {
    $complete_chain = $this->referenceChainComplete();
    $data = $this->recursiveArraySearch($complete_chain, $plugin_label);
    $return_data = [];
    foreach ($data as $reference_chain) {
      $label = array_shift($reference_chain);
      $return_data[$label][] = $this->buildNestedArray($reference_chain, $plugin_label);
    }
    return $return_data;
  }

  /**
   * Recursively search an array for a value.
   *
   * @param array $array
   *   The array to search.
   * @param mixed $search_value
   *   The value to search for.
   * @param array $path
   *   The path to the value.
   *
   * @return array
   *   The matching paths.
   */
  public function recursiveArraySearch($array, $search_value, $path = []) {
    $matching_paths = [];
    foreach ($array as $key => $value) {
      if ($key === $search_value || $value === $search_value) {
        $matching_paths[] = $path;
      }
      if (is_array($value)) {
        $subPath = array_merge($path, [$key]);
        $matching_paths = array_merge($matching_paths, $this->recursiveArraySearch($value, $search_value, $subPath));
      }
    }
    return $matching_paths;
  }

  /**
   * Build a nested array from a list of keys.
   *
   * @param array $keys
   *   The keys to build the array from.
   * @param string $plugin_label
   *   The plugin label to add to the final key.
   *
   * @return array
   *   The nested array.
   */
  public function buildNestedArray($keys, $plugin_label) {
    $result = [];
    $current = &$result;
    $final_key = count($keys) - 1;
    foreach ($keys as $key => $value) {
      if ($key === $final_key) {
        $current[$value] = $plugin_label;
      }
      else {
        $current[$value] = [];
      }
      $current = &$current[$value];
    }
    return $result;
  }

}
