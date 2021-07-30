<?php

namespace Drupal\sfgov_doc_html\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\sfgov_doc_html\Annotation\DocFormatter;

/**
 * Plugin manager for doc formatters.
 */
class DocFormatterManager extends DefaultPluginManager implements DocFormatterManagerInterface {

  /**
   * DocFormatterManager constructor.
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
      'Plugin/DocFormatter',
      $namespaces,
      $module_handler,
      DocFormatterInterface::class,
      DocFormatter::class
    );
    $this->alterInfo('sfgov_doc_html_doc_formatter_info');
    $this->setCacheBackend($cache_backend, 'sfgov_doc_html_doc_formatter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions =  parent::getDefinitions();
    uasort($definitions, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $definitions;
  }

}
