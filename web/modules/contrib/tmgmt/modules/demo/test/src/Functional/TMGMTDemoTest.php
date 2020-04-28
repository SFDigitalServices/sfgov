<?php

namespace Drupal\tmgmt_demo\Tests;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\tmgmt\Functional\TMGMTTestBase;

/**
 * Tests the demo module for TMGMT.
 *
 * @group TMGMT
 */
class TMGMTDemoTest extends TMGMTTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array('tmgmt_demo', 'ckeditor');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $basic_html_format = FilterFormat::load('basic_html');
    $restricted_html_format = FilterFormat::create(array(
      'format' => 'restricted_html',
      'name' => 'Restricted HTML',
    ));
    $restricted_html_format->save();
    $full_html_format = FilterFormat::create(array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    ));
    $full_html_format->save();
    $this->loginAsAdmin([
      'access content overview',
      'administer tmgmt',
      'translate any entity',
      'edit any translatable_node content',
      $basic_html_format->getPermissionName(),
      $restricted_html_format->getPermissionName(),
      $full_html_format->getPermissionName(),
    ]);
  }

  /**
   * Asserts translation jobs can be created.
   */
  public function testInstalled() {
    // Try and translate node 1.
    $this->drupalGet('node');
    $this->assertText('First node');
    $this->assertText('Second node');
    $this->assertText('TMGMT Demo');
    $this->clickLink(t('First node'));
    $this->clickLink(t('Translate'));
    $edit = [
      'languages[de]' => TRUE,
      'languages[fr]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Request translation'));
    $this->assertText(t('2 jobs need to be checked out.'));
    // Try and translate node 2.
    $this->drupalGet('admin/content');
    $this->clickLink(t('Second node'));
    $this->clickLink(t('Translate'));
    $this->drupalPostForm(NULL, $edit, t('Request translation'));
    $this->assertText(t('2 jobs need to be checked out.'));

    // Test local translator.
    $edit = [
      'translator' => 'local',
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit to provider and continue');
    $this->assertText('The translation job has been submitted.');

    // Check to see if no items are selected and the error message pops up.
    $this->drupalPostForm('admin/tmgmt/sources', [], t('Request translation'));
    $this->assertUniqueText(t("You didn't select any source items."));
    $this->drupalPostForm(NULL, [], t('Search'));
    $this->assertNoText(t("You didn't select any source items."));
    $this->drupalPostForm(NULL, [], t('Cancel'));
    $this->assertNoText(t("You didn't select any source items."));
    $this->drupalPostForm(NULL, [], t('Add to cart'));
    $this->assertUniqueText(t("You didn't select any source items."));

    // Test if the formats are set properly.
    $this->drupalGet('node/1/edit');
    $this->assertOptionSelected('edit-body-0-format--2', 'basic_html', 'Basic HTML selected as format');
    $this->drupalGet('node/2/edit');
    $this->assertOptionSelected('edit-body-0-format--2', 'restricted_html', 'Restricted HTML selected as format');
    $this->drupalGet('node/3/edit');
    $this->assertOptionSelected('edit-body-0-format--2', 'full_html', 'Full HTML selected as format');
  }

}
