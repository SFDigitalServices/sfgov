<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Base class for External Link tests.
 *
 * Provides common setup stuff and various helper functions.
 */
abstract class ExtlinkTestBase extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['extlink', 'node', 'filter'];

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
   * Normal visitor with limited permissions.
   *
   * @var Drupaluser
   */
  protected $emptyFormat;

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
    $this->adminUser->roles[] = 'administrator';
    $this->adminUser->save();

    // Create page content type that we will use for testing.
    $this->drupalCreateContentType(['type' => 'page']);

    // Add a text format with minimum data only.
    $this->emptyFormat = FilterFormat::create([
      'format' => 'empty_format',
      'name' => 'Empty format',
    ]);
    $this->emptyFormat->save();
  }

}
