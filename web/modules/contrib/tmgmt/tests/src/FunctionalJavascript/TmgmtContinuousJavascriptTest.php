<?php

namespace Drupal\Tests\tmgmt\Functional;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Verifies functionality of translator handling.
 *
 * @group tmgmt
 */
class TmgmtContinuousJavascriptTest extends WebDriverTestBase {

  use TmgmtTestTrait;
  use TmgmtEntityTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'tmgmt',
    'tmgmt_test',
    'tmgmt_content',
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
    $this->loginAsAdmin([
      'translate any entity',
      'create content translations',
    ]);
    $this->addLanguage('de');
    $this->addLanguage('es');

    $this->drupalPlaceBlock('system_breadcrumb_block');

    $this->createNodeType('page', 'Page', TRUE);
    $this->createNodeType('article', 'Article', TRUE);
  }

  /**
   * Test continuous job form improvements.
   */
  public function testContinuousJobForm() {
    // Create two new node types one not enabled for translation.
    $this->createNodeType('page1', 'Enabled page', TRUE);
    $this->createNodeType('article1', 'Not enabled article', FALSE);

    // Create a page, request a translation for de and es to initiate a
    // checkout queue.
    $node = $this->createNode([
      'type' => 'page',
    ]);
    $edit = array(
      'languages[de]' => TRUE,
      'languages[es]' => TRUE,
    );

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalPostForm('node/' . $node->id() . '/translations', $edit, t('Request translation'));
    $assert_session->pageTextContains('2 jobs need to be checked out.');
    $assert_session->pageTextContains('Submit all 2 translation jobs with the same settings');

    // Create continuous job through the form.
    $this->drupalGet('admin/tmgmt/continuous_jobs/continuous_add');
    // Test we don't have selected source language in target language dropdown.
    $page->selectFieldOption('Source language', 'de');
    $assert_session->assertWaitOnAjaxRequest();
    $options = $this->xpath('//*[@name="target_language"]/option');
    $this->assertCount(2, $options);
    $this->assertEquals('English', $options[0]->getText());
    $this->assertEquals('Spanish', $options[1]->getText());

    // Make sure that no checkout queue UI elements are shown.
    $assert_session->pageTextNotContains('jobs pending');
    $assert_session->pageTextNotContains('Submit all');
    $assert_session->responseNotContains('progress__track');

    $continuous_job_label = strtolower($this->randomMachineName());
    $edit_job = [
      'label[0][value]' => $continuous_job_label,
      'target_language' => 'es',
      'continuous_settings[content][node][enabled]' => TRUE,
    ];

    $this->drupalPostForm(NULL, $edit_job, t('Save job'));
    // Check we don't see not enabled article in content type list.
    $this->clickLink('Manage');
    $assert_session->pageTextContains(t('Enabled page'));
    $assert_session->pageTextNotContains(t('Not enabled article'));
  }

}
