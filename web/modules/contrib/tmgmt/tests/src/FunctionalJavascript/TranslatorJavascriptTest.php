<?php

namespace Drupal\Tests\tmgmt\Functional;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Verifies functionality of translator handling.
 *
 * @group tmgmt
 */
class TranslatorJavascriptTest extends WebDriverTestBase {

  use TmgmtTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'tmgmt',
    'tmgmt_test',
    'node',
    'block',
    'locale',
  );

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Login as admin to be able to set environment variables.
    $this->loginAsAdmin();
    $this->addLanguage('de');
    $this->addLanguage('es');
    $this->addLanguage('el');

    // Login as translation administrator to run these tests.
    $this->loginAsTranslator(array(
      'administer tmgmt',
    ), TRUE);
  }

  /**
   * Test multiple local language with one single remote language.
   */
  public function testMultipleLocalToSingleRemoteMapping() {
    $this->addLanguage('pt-br');
    $this->addLanguage('pt-pt');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add mapping to the file translator.
    $edit = array(
      'remote_languages_mappings[pt-br]' => 'pt',
      'remote_languages_mappings[pt-pt]' => 'pt',
    );
    $this->drupalPostForm('admin/tmgmt/translators/manage/test_translator', $edit, t('Save'));
    $this->drupalGet('admin/tmgmt/translators/manage/test_translator');
    $assert_session->fieldValueEquals('edit-remote-languages-mappings-pt-br', 'pt');

    // Test first local language.
    $job = tmgmt_job_match_item('en', 'pt-br');
    $job->addItem('test_source', 'test', 0);
    $edit = array(
      'target_language' => 'pt-br',
    );
    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());

    $page->selectFieldOption('Target language', 'pt-br');
    $this->assertFieldByXPath('//select[@id="edit-translator"]//option[@value="test_translator"]', t('Test provider'), 'Provider maps correctly');
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $assert_session->pageTextContains('Portuguese, Brazil');

    $this->assertEquals(1, count($job->getItems()));
    $this->assertTrue($job->getItems()[1]->accepted());
    $this->drupalGet('admin/tmgmt/items/' . 1);
    $assert_session->pageTextContains('pt-br(pt): Text for job item with type ' . $job->getItems()[1]->getItemType() . ' and id ' . $job->getItems()[1]->getItemId() . '.');
    $this->assertEquals('pt-br', $job->getTargetLangcode());

    // Test the other local language.
    $job = tmgmt_job_match_item('en', 'pt-pt');
    $job->addItem('test_source', 'test', 0);
    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());
    $page->selectFieldOption('Target language', 'pt-pt');
    $this->assertFieldByXPath('//select[@id="edit-translator"]//option[@value="test_translator"]', t('Test provider'), 'Provider maps correctly');
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $assert_session->pageTextContains('Portuguese, Portugal');
    $this->assertEqual(count($job->getItems()), 1);
    $this->assertTrue($job->getItems()[2]->accepted());
    $this->drupalGet('admin/tmgmt/items/' . 2);
    $assert_session->pageTextContains('pt-pt(pt): Text for job item with type ' . $job->getItems()[2]->getItemType() . ' and id ' . $job->getItems()[2]->getItemId() . '.');
    $this->assertEquals('pt-pt', $job->getTargetLangcode());
  }

}
