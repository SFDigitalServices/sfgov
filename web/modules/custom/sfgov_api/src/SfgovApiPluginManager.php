<?php

namespace Drupal\sfgov_api;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * SfgovApi plugin manager.
 */
class SfgovApiPluginManager extends DefaultPluginManager {

  /**
   * Constructs SfgovApiPluginManager object.
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
      'Plugin/SfgovApi',
      $namespaces,
      $module_handler,
      'Drupal\sfgov_api\SfgovApiInterface',
      'Drupal\sfgov_api\Annotation\SfgovApi'
    );
    $this->alterInfo('sfgov_api_info');
    $this->setCacheBackend($cache_backend, 'sfgov_api_plugins');
  }

}
