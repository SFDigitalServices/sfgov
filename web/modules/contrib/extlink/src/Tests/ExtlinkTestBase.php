<?php

namespace Drupal\extlink\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for External Link tests.
 *
 * Provides common setup stuff and various helper functions.
 */
abstract class ExtlinkTestBase extends WebTestBase {

  public static $modules = ['extlink'];

  /**
   * User with various administrative permissions.
   *
   * @var Drupaluser
   */
  protected $adminUser;

  /**
   * Normal visitor with limited permissions.
   *
   * @var Drupaluser
   */
  protected $normalUser;

  /**
   * Drupal path of the (general) External Links admin page.
   */
  const EXTLINK_ADMIN_PATH = 'admin/config/user-interface/extlink';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Enable any module that you will need in your tests.
    parent::setUp();
    // Create a normal user.
    $permissions = [];
    $this->normalUser = $this->drupalCreateUser($permissions);

    // Create an admin user.
    $permissions[] = 'administer site configuration';
    $permissions[] = 'administer permissions';
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

}
