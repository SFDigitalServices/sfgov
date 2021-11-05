<?php

namespace Drupal\Tests\protected_pages\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides functional Drupal tests for access to protected pages and config.
 *
 * @group protected_pages
 */
class ProtectedPagesAccess extends BrowserTestBase {
  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'protected_pages'];

  /**
   * A user with permission to 'access protected page password screen'.
   *
   * @var object
   */
  protected $user;

  /**
   * A user with permission to 'administer protected pages configuration'.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test access to a Protected Page.
   */
  public function testProtectedPageAccess() {
    // Create a node.
    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode();
    $this->assertNotEmpty(Node::load($node->id()), 'Node created.');

    // Protect created node.
    $page_data = [
      'password' => 'test_pass',
      'path' => '/node/' . $node->id(),
    ];
    $storage = \Drupal::service('protected_pages.storage');
    $storage->insertProtectedPage($page_data);

    $page_path = 'node/' . $node->id();

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    drupal_flush_all_caches();

    // Create a user w/ permission
    // to 'access protected page password screen'.
    $user = $this->drupalCreateUser(['access protected page password screen']);

    // View the node as an Anonymous user.
    // User should see Access Denied.
    $this->drupalGet($page_path);
    $this->assertSession()->statusCodeEquals(403);

    // View the node again as an Anonymous user to ensure that page cache
    // does not break page protection. See
    // https://www.drupal.org/project/protected_pages/issues/2973524.
    $this->drupalGet($page_path);
    $this->assertSession()->statusCodeEquals(403);

    // Login as a user w/ permission to 'access protected page password screen'.
    // Ensure the user can use password screen.
    $this->drupalLogin($user);
    $this->drupalGet($page_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Enter Password');
    $this->assertSession()->addressEquals('protected-page?destination=/node/1&protected_page=1');
    $this->drupalLogout($user);
  }

  /**
   * Test access to Protected Pages Configuration screen.
   */
  public function testProtectedPageConfigurationAccess() {
    // Create a user w/ permission
    // to 'administer protected pages configuration'.
    $adminUser = $this->drupalCreateUser(['administer protected pages configuration']);

    // Create a user w/ permission
    // to 'access protected page password screen'.
    $user = $this->drupalCreateUser(['access protected page password screen']);

    // Test access to Protected Pages Configuration screen.
    $this->drupalLogin($user);
    $this->drupalGet('admin/config/system/protected_pages');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout($user);

    $this->drupalLogin($adminUser);
    $this->drupalGet('admin/config/system/protected_pages');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout($adminUser);
  }

}
