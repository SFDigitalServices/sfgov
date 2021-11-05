<?php

namespace Drupal\Tests\fixed_block_content\Functional;

/**
 * Tests the fixed block content admin.
 *
 * @group fixed_block_content
 */
class FixedBlockAdminTest extends FunctionalFixedBlockTestBase {

  /**
   * A test user with permission to administer blocks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Random content to work with.
   *
   * @var string
   */
  protected $randomContent;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
      'view the administration theme',
    ]);
    $this->drupalLogin($this->adminUser);

    // Add the local task links.
    $this->drupalPlaceBlock('local_tasks_block', []);
    // Add the page title block.
    $this->drupalPlaceBlock('page_title_block', []);

    // Generate a random content to work with.
    $this->randomContent = $this->randomMachineName(128);
  }

  /**
   * Full admin test sequence.
   */
  public function testAdmin() {
    $this->doTestExportConfirmFormNoDefaultContent();
    $this->doTestImportConfirmForm();
    $this->doTestExportConfirmFormWithDefaultContent();
    $this->doTestExportConfirmFormWithDefaultContentOverExisting();
    $this->doTestFixedBlockDeleteForm();
  }

  /**
   * Tests the default content export confirm form.
   *
   * @see \Drupal\fixed_block_content\Form\ExportConfirmForm
   */
  public function doTestExportConfirmFormNoDefaultContent() {
    // Go to export (restore) block with the default content page.
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Custom block library');
    $this->clickLink('Fixed blocks');
    $this->clickLink('Restore default content');
    $this->assertText('Are you sure you want to restore the Basic fixed to its default content?');
    // Confirm the form.
    $this->drupalPostForm(NULL, [], 'Confirm');
    $block_content = $this->fixedBlock->getBlockContent(FALSE);
    // The block content must be created.
    $this->assertNotNull($block_content);
    // And it must has no body content.
    $this->assertEmpty($block_content->get('body')->getString());
  }

  /**
   * Tests the default content import confirm form.
   *
   * @see \Drupal\fixed_block_content\Form\ImportConfirmForm
   */
  public function doTestImportConfirmForm() {
    // Gets the block content.
    $block_content = $this->fixedBlock->getBlockContent();
    // Set random content on its body field.
    $block_content->get('body')->setValue($this->randomContent);
    $block_content->save();

    // Go to import (save into fixed) default content page.
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Custom block library');
    $this->clickLink('Fixed blocks');
    $this->clickLink('Set contents as default');
    $this->assertText('Are you sure you want to set the Basic fixed current content as the default?');
    // Confirm the form.
    $this->drupalPostForm(NULL, [], 'Confirm');
    // Update the fixed block content object.
    $this->fixedBlock = $this->container->get('entity_type.manager')
      ->getStorage('fixed_block_content')->load('basic_fixed');
    // The body content must be in the the default content.
    if (method_exists($this, 'assertStringContainsString')) {
      // PHPUnit >= 7.5.0.
      $this->assertStringContainsString($this->randomContent, $this->fixedBlock->get('default_content'));
    }
    else {
      $this->assertContains($this->randomContent, $this->fixedBlock->get('default_content'));
    }
  }

  /**
   * Tests the default content export confirm form.
   *
   * @see \Drupal\fixed_block_content\Form\ExportConfirmForm
   */
  public function doTestExportConfirmFormWithDefaultContent() {
    // Delete the current block content.
    $block_content = $this->fixedBlock->getBlockContent(FALSE);
    $block_content->delete();

    // Go to export (restore) block with the default content page.
    $this->drupalGet('admin/structure/block/block-content/fixed-block-content/manage/basic_fixed/export');
    $this->assertText('Are you sure you want to restore the Basic fixed to its default content?');
    $this->drupalPostForm(NULL, [], 'Confirm');

    // A new block must has been created.
    $block_content = $this->fixedBlock->getBlockContent(FALSE);
    $this->assertNotNull($block_content);
    // Check that the body fields contains the expected default content.
    $this->assertEquals($block_content->get('body')->getString(), $this->randomContent);
  }

  /**
   * Tests the default content export confirm form over existing block.
   *
   * @see \Drupal\fixed_block_content\Form\ExportConfirmForm
   */
  public function doTestExportConfirmFormWithDefaultContentOverExisting() {
    // Set arbitrary body content in the existing block content.
    $block_content = $this->fixedBlock->getBlockContent(FALSE);
    $block_content->get('body')->setValue($this->randomString(128));
    $block_content->save();

    // Go to export (restore) block with the default content page.
    $this->drupalGet('admin/structure/block/block-content/fixed-block-content/manage/basic_fixed/export');
    // The update existing option must be present.
    $this->assertText('Update the existing block content');
    // Proceed enabling the update existing option.
    $this->drupalPostForm(NULL, ['update_existing' => TRUE], 'Confirm');

    // The block content must be the same as the previously existing.
    $new_block_content = $this->fixedBlock->getBlockContent(FALSE);
    $this->assertEquals($block_content->id(), $new_block_content->id());
    $this->assertEquals($block_content->uuid(), $new_block_content->uuid());

    // The body content must be updated to the defaults.
    $this->assertEquals($new_block_content->get('body')->getString(), $this->randomContent);
  }

  /**
   * Tests the fixed block delete form.
   *
   * @see \Drupal\fixed_block_content\Form\FixedBlockContentDeleteForm
   */
  public function doTestFixedBlockDeleteForm() {
    // Block content must exists from previous step.
    $block_content_id = $this->fixedBlock->getBlockContent(FALSE)->id();

    // Go to delete the block content.
    $this->drupalGet('admin/structure/block/block-content/fixed-block-content');
    $this->clickLink('Delete');
    // The "Delete the linked custom block as well" must be present in the form.
    $this->assertText('Delete the linked custom block as well');
    // Enable it.
    $edit = ['delete_linked_block' => TRUE];
    // Confirm the form.
    $this->drupalPostForm(NULL, $edit, 'Delete');
    $this->assertText('The fixed block content Basic fixed has been deleted.');

    // Test that the fixed block content was deleted.
    $this->fixedBlock = $this->container->get('entity_type.manager')
      ->getStorage('fixed_block_content')->load('basic_fixed');
    $this->assertNull($this->fixedBlock);

    // Test that the block content was deleted as well.
    $block_content = $this->container->get('entity_type.manager')
      ->getStorage('block_content')->loadUnchanged($block_content_id);
    $this->assertNull($block_content);
  }

}
