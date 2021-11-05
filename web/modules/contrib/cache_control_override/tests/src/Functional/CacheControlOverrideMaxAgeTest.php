<?php

namespace Drupal\Tests\cache_control_override\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the cache control override.
 *
 * @group cache_control_override
 */
class CacheControlOverrideMaxAgeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'cache_control_override',
    'cache_control_override_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();
  }

  /**
   * Test the cache properties in response header data.
   */
  public function testMaxAge() {

    $this->drupalGet('cco');
    $this->assertSession()->responseContains('Max age test content');
    $this->assertSession()->responseHeaderContains('Cache-Control', 'max-age=3600, public');

    $this->drupalGet('cco/333');
    $this->assertSession()->responseHeaderContains('Cache-Control', 'max-age=333, public');

    $this->drupalGet('cco/0');
    $this->assertSession()->responseHeaderContains('Cache-Control', 'max-age=0, public');
  }

}
