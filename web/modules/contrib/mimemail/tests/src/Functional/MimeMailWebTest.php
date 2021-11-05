<?php

namespace Drupal\Tests\mimemail\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\mimemail\Utility\MimeMailFormatHelper;

/**
 * Mime Mail web tests.
 *
 * @group mimemail
 */
class MimeMailWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'mailsystem',
    'mimemail',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with all permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->createUser([
      'access administration pages',
      'administer site configuration',
    ]);

    // Log in admin user.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that spaces in attachment filenames are properly URL-encoded.
   */
  public function testUrl() {
    $this->drupalPostForm(
      'admin/config/system/mimemail',
      ['linkonly' => TRUE],
      'Save configuration'
    );

    $url = 'public://' . $this->randomMachineName() . ' ' . $this->randomMachineName() . '.jpg';
    $result = MimeMailFormatHelper::mimeMailUrl($url, TRUE);
    $expected = str_replace(' ', '%20', file_create_url($url));
    $message = 'Stream wrapper converted to web accessible URL for linked image.';
    $this->assertSame($result, $expected, $message);
  }

}
