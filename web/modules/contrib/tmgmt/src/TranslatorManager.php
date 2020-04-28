<?php

namespace Drupal\tmgmt;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for translator plugins.
 */
class TranslatorManager extends DefaultPluginManager {

  /**
   * Array of instantiated translator UI instances.
   *
   * @var array
   */
  protected $ui = array();

  /**
   * Constructs a ConditionManager object.
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
    parent::__construct('Plugin/tmgmt/Translator', $namespaces, $module_handler, 'Drupal\tmgmt\TranslatorPluginInterface', 'Drupal\tmgmt\Annotation\TranslatorPlugin');
    $this->alterInfo('tmgmt_translator_info');
    $this->setCacheBackend($cache_backend, 'tmgmt_translator');
  }

  /**
   * Returns a translator plugin UI instance.
   *
   * @param string $plugin
   *   Name of the translator plugin.
   *
   * @return \Drupal\tmgmt\TranslatorPluginUiInterface
   *   Instance a translator plugin UI instance.
   */
  public function createUIInstance($plugin) {
    if (!isset($this->ui[$plugin])) {
      $definition = $this->getDefinition($plugin);
      $class = $definition['ui'];
      $this->ui[$plugin] = new $class(array(), $plugin, $definition);
    }
    return $this->ui[$plugin];
  }

  /**
   * Returns the translator plugin labels.
   *
   * @return array
   *   Array of plugin labels, keyed by the plugin id.
   */
  public function getLabels() {
    $list = array();
    foreach ($this->getDefinitions() as $plugin => $definition) {
      $list[$plugin] = $definition['label'];
    }
    return $list;
  }

}
