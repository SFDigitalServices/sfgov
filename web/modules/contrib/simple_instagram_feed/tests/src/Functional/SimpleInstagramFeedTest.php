<?php

namespace Drupal\Tests\simple_instagram_feed\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a class for Simple Instagram Feed functional tests.
 *
 * @group block
 */
class SimpleInstagramFeedTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['simple_instagram_feed'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * Admin users with all permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'view the administration theme',
      'access administration pages',
      'administer blocks',
    ]);
  }

  /**
   * Tests Simple Instagram Feed block.
   */
  public function testsSimpleInstagramFeedBlock() {
    $this->drupalLogin($this->adminUser);

    // Block is listed in site block library.
    $this->drupalGet('admin/structure/block/library/stable');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Simple Instagram Feed');

    // Add block form is avialable.
    $theme = \Drupal::service('theme_handler')->getDefault();
    $this->drupalGet("admin/structure/block/add/simple_instagram_block/$theme");
    $this->assertSession()->statusCodeEquals(200);
  }

}
