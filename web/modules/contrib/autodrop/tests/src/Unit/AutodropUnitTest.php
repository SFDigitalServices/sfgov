<?php

namespace Drupal\Tests\autodrop\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @group autodrop
 */
class AutodropUnitTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    include_once __DIR__ . '/../../../autodrop.module';
  }
    /**
   * @test
   * @covers ::autodrop_library_info_alter
   */
  public function libraryAlter() {
    $libraries = [
      'drupal.dropbutton' => [
        'dependencies' => [
          'core/jquery',
          'core/drupal',
          'core/drupalSettings',
          'core/jquery.once',
        ]
      ]
    ];

    autodrop_library_info_alter($libraries, 'core');
    self::assertArrayEquals([
      'core/jquery',
      'core/drupal',
      'core/drupalSettings',
      'core/jquery.once',
      'autodrop/dropbutton',
    ], $libraries['drupal.dropbutton']['dependencies']);
  }
}
