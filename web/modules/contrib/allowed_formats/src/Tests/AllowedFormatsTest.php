<?php

namespace Drupal\allowed_formats\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\filter\Entity\FilterFormat;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the basic functionality of Allowed Formats.
 *
 * @group allowed_formats
 */
class AllowedFormatsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'allowed_formats', 'field_ui'];

  /**
  * A user with relevant administrative privileges.
  *
  * @var \Drupal\user\UserInterface
  */
  protected $adminUser;

  /**
   * A user with privileges to edit a text field.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('administer filters', 'administer entity_test fields'));
    $this->webUser = $this->drupalCreateUser(array('administer entity_test content'));
  }

  /**
   * Test widgets for fields with selected allowed formats.
   */
  function testAllowedFormats() {

    // Create one text format.
    $format1 = FilterFormat::create([
      'format' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
      'roles' => [$this->webUser->getRoles()[0]],
    ]);
    $format1->save();

    // Create a second text format.
    $format2 = FilterFormat::create([
      'format' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
      'roles' => [$this->webUser->getRoles()[0]],
    ]);
    $format2->save();

    // Change the Allowed Formats settings of the test field created by
    // entity_test_install().
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('entity_test/structure/entity_test/fields/entity_test.entity_test.field_test_text', [
      'third_party_settings[allowed_formats][' . $format1->id() . ']' => TRUE,
      'third_party_settings[allowed_formats][' . $format2->id() . ']' => TRUE,
    ], t('Save settings'));

    // Display the creation form.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("field_test_text[0][value]", NULL, 'Widget is displayed');
    $this->assertFieldByName("field_test_text[0][format]", NULL, 'Format selector is displayed');

    // Change field to allow only one format.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('entity_test/structure/entity_test/fields/entity_test.entity_test.field_test_text', [
      'third_party_settings[allowed_formats][' . $format2->id() . ']' => FALSE,
    ], t('Save settings'));

    // We shouldn't have the 'format' selector since only one format is allowed.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("field_test_text[0][value]", NULL, 'Widget is displayed');
    $this->assertNoFieldByName("field_test_text[0][format]", NULL, 'Format selector is not displayed');
  }
}
