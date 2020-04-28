<?php

namespace Drupal\config_exclude\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ExcludeFilter.
 * Activate the filter by declaring $settings['config_exclude_modules'] in your
 * settings.php file, eg:
 *
 * @code
 *   $settings['config_exclude_modules'] = ['devel', 'stage_file_proxy'];
 * @endcode
 *
 * This filter has a low weight so it is applied before config_split.
 *
 * @ConfigFilter(
 *   id = "config_exclude",
 *   label = @Translation("Config Exclude"),
 *   storages = {"config.storage.sync"},
 *   weight = -10,
 * )
 */
class ExcludeFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  /**
   * A somewhat arbitrary string that works better than an empty string when
   * used as array key. Yes, StorageInterface::DEFAULT_COLLECTION, we're looking
   * at you.
   */
  const DEFAULT_COLLECTION_KEY = 'default';

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Config\ConfigManagerInterface
   *   The config manager service.
   */
  protected $configManager;

  /**
   * @var string[]
   *   A list of excluded module names.
   */
  protected $excludedModules;

  /**
   * @var mixed[][]
   *   A list of excluded config arrays, keyed by config name, grouped by config
   *   collection.
   */
  protected $excludedConfig;

  /**
   * Constructs a new ExcludeFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigManagerInterface $manager
   *   The config manager for retrieving dependent config.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigManagerInterface $manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configManager = $manager;
    $this->moduleHandler = $module_handler;

    $this->initExcludedModules();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Initializes arrays of active modules and dependent config to be excluded.
   */
  protected function initExcludedModules() {
    $this->excludedModules = [];
    $this->excludedConfig = [];

    $excluded_modules_setting = Settings::get('config_exclude_modules', []);
    foreach ($excluded_modules_setting as $module_name) {

      // Select only active modules.
      if ($this->moduleHandler->moduleExists($module_name)) {

        // Remember excluded module names.
        $this->excludedModules[$module_name] = $module_name;

        // Find dependent config objects and entities of excluded modules. The
        // method to discover dependent config is inspired by what
        // ConfigManager::uninstall() does to find dependent config.
        $config_object_names = $this->configManager->getConfigFactory()->listAll($module_name . '.');
        $config_entity_names = array_keys($this->configManager->findConfigEntityDependents('module', [$module_name]));
        $config_item_names = array_merge($config_object_names, $config_entity_names);

        // Load all dependent config items.
        $config_items = $this->configManager->getConfigFactory()->loadMultiple($config_item_names);

        // Store the dependent excluded config, keyed by config collection and
        // name, so we can merge it into config when config is being read or
        // filter it out when config is being written.
        foreach ($config_items as $config_item_name => $config_item) {
          $collection_key = $this->getCollectionKey($config_item->getStorage()->getCollectionName());
          $this->excludedConfig[$collection_key][$config_item_name] = $config_item->get();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    if ($name === 'core.extension') {
      foreach ($this->excludedModules as $module_name => $info) {
        $data['module'][$module_name] = 0;
      }
    }
    else {
      $collection_key = $this->getCollectionKey($this->source->getCollectionName());
      if (isset($this->excludedConfig[$collection_key][$name])) {
        $data = $this->excludedConfig[$collection_key][$name];
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    if (in_array('core.extension', $names)) {
      foreach ($this->excludedModules as $module_name => $info) {
        $data['core.extension']['module'][$module_name] = 0;
      }
    }

    $collection_key = $this->getCollectionKey($this->source->getCollectionName());
    if (!empty($this->excludedConfig[$collection_key])) {
      foreach ($this->excludedConfig[$collection_key] as $name => $config) {
        $data[$name] = $config;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    $collection_key = $this->getCollectionKey($this->source->getCollectionName());
    if (!empty($this->excludedConfig[$collection_key])) {
      $data = array_unique(array_merge($data, array_keys($this->excludedConfig[$collection_key])));
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    if ($name === 'core.extension') {
      $exclude = $this->excludedModules;
      $data['module'] = array_diff_key($data['module'], $exclude);
    }
    else {
      $collection_key = $this->getCollectionKey($this->source->getCollectionName());
      if (isset($this->excludedConfig[$collection_key][$name])) {
        return NULL;
      }
    }
    return $data;
  }

  /**
   * Helper function, turns a collection name into a string usable as array key.
   *
   * @param string $collection_name
   *   A collection name, which can be an empty string in case of the default
   *   collection.
   * @return string
   *   The collection name, or self::CONFIG_DEFAULT_COLLECTION.
   */
  protected function getCollectionKey($collection_name) {
    return $collection_name === StorageInterface::DEFAULT_COLLECTION ? self::DEFAULT_COLLECTION_KEY : $collection_name;
  }

}
