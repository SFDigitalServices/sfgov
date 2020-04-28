<?php

namespace Drupal\Tests\tmgmt\Functional;

/**
 * Tests the translator add, edit and overview user interfaces.
 *
 * @group tmgmt
 */
class TranslatorUITest extends TMGMTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static public $modules = array('tmgmt_file');

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Login as administrator to add/edit and view translators.
    $this->loginAsAdmin();
  }

  /**
   * Tests UI for creating a translator.
   */
  public function testTranslatorUI() {

    // Test translator creation UI.
    $this->drupalGet('admin/tmgmt/translators/add');
    $this->drupalPostForm('admin/tmgmt/translators/add', array(
      'label' => 'Test translator',
      'description' => 'Test translator description',
      'name' => 'translator_test',
      'settings[scheme]' => 'private',
    ), t('Save'));
    $this->assertText('Test translator configuration has been created.');
    // Test translator edit page.
    $this->drupalGet('admin/tmgmt/translators/manage/translator_test');
    $this->assertFieldByName('label', 'Test translator');
    $this->assertFieldByName('description', 'Test translator description');
    $this->assertFieldByName('name', 'translator_test');
    $this->assertFieldChecked('edit-settings-scheme-private');
    $this->drupalPostForm(NULL, array(
      'label' => 'Test translator changed',
      'description' => 'Test translator description changed',
    ), t('Save'));
    $this->assertText('Test translator changed configuration has been updated.');

    // Test translator overview page.
    $this->drupalGet('admin/tmgmt/translators');
    $this->assertRaw('<img class="tmgmt-logo-overview"');
    $this->assertText('Test translator changed');
    $this->assertLink(t('Edit'));
    $this->assertLink(t('Delete'));

    // Check if the edit link is displayed before the clone link.
    $content = $this->getSession()->getPage()->getContent();
    $edit_position = strpos($content, '<li class="edit">');
    $clone_position = strpos($content, '<li class="clone">');
    $this->assertTrue($edit_position < $clone_position);
  }

}
