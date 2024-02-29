<?php

namespace Drupal\sfgov_api;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

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
   */
  public function fetchPayload($plugin_label, $langcode, $entity_id) {
    $plugin = $this->createInstance($plugin_label, [
      'langcode' => $langcode,
      'entity_id' => $entity_id,
    ]);
    $payload = $plugin->getPayload();
    return $payload;
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
   * Get the plugins that reference the inputted plugin.
   *
   * @param string $plugin_label
   *   The plugin being searched for.
   */
  public function referenceChainUp($plugin_label) {
    $results = [];
    $plugins = $this->getDefinitions();
    foreach ($plugins as $plugin) {
      if ($plugin['referenced_plugins']) {
        if (in_array($plugin_label, $plugin['referenced_plugins'])) {
          $results[] = $plugin['id'];
        }
      }
    }

    return $this->getReferenceChain($results, $plugin_label);
  }

  /**
   * Get the plugins that this plugin references.
   *
   * @param string $plugin_label
   *   The plugin being searched for.
   */
  public function referenceChainDown($plugin_label) {
    $referenced_plugins = $this->getDefinition($plugin_label)['referenced_plugins'];
    $plugin = $this->createInstance($plugin_label);
    $reference_chain = $this->getReferenceChain($referenced_plugins);
    return $reference_chain;
  }

  /**
   * Get the reference chain.
   */
  public function getReferenceChain($plugin_list) {
    $returned_plugins = [];
    foreach ($plugin_list as $plugin_name) {
      $plugin_definition = $this->getDefinition($plugin_name);
      if (is_string($plugin_name)) {
        // If its a node we don't need to continue.
        if (str_starts_with($plugin_name, 'node')) {
          $returned_plugins[$plugin_name] = '';
          continue;
        }
      }
      if ($referenced_plugins = $plugin_definition['referenced_plugins']) {
        $returned_plugins[$plugin_name] = $plugin_name;
        $returned_plugins[$plugin_name] = $this->getReferenceChain($referenced_plugins);
      }
      else {
        $returned_plugins[$plugin_name] = '';
      }
    }
    return $returned_plugins;
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

}
