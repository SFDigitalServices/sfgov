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
