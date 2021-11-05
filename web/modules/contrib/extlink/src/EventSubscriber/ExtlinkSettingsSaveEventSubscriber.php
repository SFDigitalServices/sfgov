<?php

namespace Drupal\extlink\EventSubscriber;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clears JS and asset libraries in response to changes in extlink settings.
 */
class ExtlinkSettingsSaveEventSubscriber implements EventSubscriberInterface {

  /**
   * The CSS/JS asset library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The JS asset optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $jsOptimizer;

  /**
   * ExtlinkSettingsSaveEventSubscriber constructor.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The CSS/JS asset library discovery service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_optimizer
   *   The JS asset optimizer service.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, AssetCollectionOptimizerInterface $js_optimizer) {
    $this->libraryDiscovery = $library_discovery;
    $this->jsOptimizer = $js_optimizer;
  }

  /**
   * Acts on changes to extlink.settings to flush JS library and assets.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    if ($config->getName() === 'extlink.settings') {
      $flush_js_files = $config->get('extlink_use_external_js_file');

      if ($event->isChanged('extlink_use_external_js_file')) {
        // When using external JS file is enabled or disabled, need to flush the
        // library discovery cache to update the dependencies of drupal.extlink
        // library.
        $this->libraryDiscovery->clearCachedDefinitions();
        $flush_js_files = TRUE;
      }

      if ($flush_js_files) {
        // Flush the optimized JS files if using an external JS file when the
        // settings are saved. Also flush the optimized JS files when disabling
        // or enabling using the external JS files.
        $this->jsOptimizer->deleteAll();
        _drupal_flush_css_js();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [ConfigEvents::SAVE => 'onConfigSave'];
  }

}
