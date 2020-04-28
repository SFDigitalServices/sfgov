<?php

namespace Drupal\Tests\autodrop\Kernel;

use Drupal\Tests\token\Kernel\KernelTestBase;

/**
 * @group autodrop
 */
class AutodropKernelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'autodrop'];

  /**
   * @test
   */
  public function autodropDependencyIsAdded() {
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = \Drupal::service('library.discovery');

    self::assertEquals([
      'core/jquery',
      'core/drupal',
      'core/drupalSettings',
      'core/jquery.once',
      'autodrop/dropbutton',
    ], $library_discovery->getLibrariesByExtension('core')['drupal.dropbutton']['dependencies']);
  }
}
