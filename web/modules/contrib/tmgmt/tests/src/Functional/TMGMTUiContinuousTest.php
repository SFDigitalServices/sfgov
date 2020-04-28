<?php

namespace Drupal\Tests\tmgmt\Functional;

use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;

/**
 * Verifies continuous functionality of the user interface
 *
 * @group tmgmt
 */
class TMGMTUiContinuousTest extends TMGMTTestBase {
  use TmgmtEntityTestTrait;

  public static $modules = ['tmgmt_content'];

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
   * Tests of the job item review process.
   */
  public function testContinuous() {
    // Test that continuous jobs are shown in the job overview.
    $this->container->get('module_installer')->install(['tmgmt_file'], TRUE);
    $non_continuous_translator = Translator::create([
      'name' => strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
      'plugin' => 'file',
      'remote_languages_mappings' => [],
      'settings' => [],
    ]);
    $non_continuous_translator->save();

    $continuous_job = $this->createJob('en', 'de', 0, [
      'label' => 'Continuous job',
      'job_type' => 'continuous',
    ]);

    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => JobInterface::STATE_CONTINUOUS]]);
    $this->assertText($continuous_job->label(), 'Continuous job is displayed on job overview page with status filter on continuous jobs.');

    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => 'job_item_' . JobItemInterface::STATE_ACTIVE]]);
    $this->assertNoText($continuous_job->label(), 'Continuous job is not displayed on job overview page if status filter is on in progress jobs.');

    // Test that there are source items checkboxes on a continuous job form.
    $this->drupalGet('admin/tmgmt/jobs/' . $continuous_job->id());
    $this->assertText($continuous_job->label());
    $this->assertNoFieldChecked('edit-continuous-settings-content-node-enabled', 'There is content checkbox and it is not checked yet.');
    $this->assertNoFieldChecked('edit-continuous-settings-content-node-bundles-article', 'There is article checkbox and it is not checked yet.');
    $this->assertNoFieldChecked('edit-continuous-settings-content-node-bundles-page', 'There is page checkbox and it is not checked yet.');

    // Enable Article source item for continuous job.
    $edit_continuous_job = [
      'continuous_settings[content][node][enabled]' => TRUE,
      'continuous_settings[content][node][bundles][article]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit_continuous_job, t('Save job'));

    // Test that continuous settings configuration is saved correctly.
    $updated_continuous_job = Job::load($continuous_job->id());
    $job_continuous_settings = $updated_continuous_job->getContinuousSettings();
    $this->assertEqual($job_continuous_settings['content']['node']['enabled'], 1, 'Continuous settings configuration for node is saved correctly.');
    $this->assertEqual($job_continuous_settings['content']['node']['bundles']['article'], 1, 'Continuous settings configuration for article is saved correctly.');
    $this->assertEqual($job_continuous_settings['content']['node']['bundles']['page'], 0, 'Continuous settings configuration for page is saved correctly.');

    // Test that continuous settings checkboxes are checked correctly.
    $this->clickLink('Manage');
    $this->assertText($continuous_job->label());
    $this->assertFieldChecked('edit-continuous-settings-content-node-enabled', 'Content checkbox is now checked.');
    $this->assertFieldChecked('edit-continuous-settings-content-node-bundles-article', 'Article checkbox now checked.');
    $this->assertNoFieldChecked('edit-continuous-settings-content-node-bundles-page', 'Page checkbox is not checked.');

    // Create continuous job through the form.
    $this->loginAsTranslator([
      'access administration pages',
      'create translation jobs',
      'submit translation jobs',
      'access user profiles',
    ], TRUE);
    $owner = $this->drupalCreateUser($this->translator_permissions);
    $this->drupalGet('admin/tmgmt/continuous_jobs/continuous_add');
    $this->assertResponse(403, 'Access denied');
    $this->loginAsAdmin();
    $this->drupalGet('admin/tmgmt/continuous_jobs/continuous_add');
    $this->assertNoText($non_continuous_translator->label());
    $continuous_job_label = strtolower($this->randomMachineName());

    $edit_job = [
      'label[0][value]' => $continuous_job_label,
      'target_language' => 'de',
      'uid' => $owner->getDisplayName(),
      'translator' => $this->default_translator->id(),
    ];
    $this->drupalPostForm(NULL, $edit_job, t('Save job'));
    $this->assertText($continuous_job_label, 'Continuous job was created.');

    // Test that previous created job is continuous job.
    $this->drupalGet('admin/tmgmt/jobs');
    $this->assertText($continuous_job_label, 'Created continuous job is displayed on job overview page.');
    // Test that job overview page with status to continuous does not have
    // Submit link.
    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => JobInterface::STATE_CONTINUOUS]]);
    $this->assertNoLink('Submit', 'There is no Submit link on job overview with status to continuous.');

    // Test that all unnecessary fields and buttons do not exist on continuous
    // job edit form.
    $this->clickLink('Manage', 0);
    $this->assertText($continuous_job_label);
    $this->assertFieldById('edit-uid', $owner->getDisplayName() . ' (' . $owner->id() . ')', 'Job owner set correctly');
    $this->assertNoRaw('<label for="edit-translator">Provider</label>', 'There is no Provider info field on continuous job edit form.');
    $this->assertNoRaw('<label for="edit-word-count">Total word count</label>', 'There is no Total word count info field on continuous job edit form.');
    $this->assertNoRaw('<label for="edit-tags-count">Total HTML tags count</label>', 'There is no Total HTML tags count info field on continuous job edit form.');
    $this->assertNoRaw('<label for="edit-created">Created</label>', 'There is no Created info field on continuous job edit form.');
    $this->assertNoRaw('id="edit-job-items-wrapper"', 'There is no Job items field on continuous job edit form.');
    $this->assertNoRaw('<div class="tmgmt-color-legend clearfix">', 'There is no Item state legend on continuous job edit form.');
    $this->assertNoRaw('id="edit-messages"', 'There is no Translation Job Messages field on continuous job edit form.');
    $this->assertNoFieldById('edit-abort-job', NULL, 'There is no Abort job button.');
    $this->assertNoFieldById('edit-submit', NULL, 'There is no Submit button.');
    $this->assertNoFieldById('edit-resubmit-job', NULL, 'There is Resubmit job button.');

    // Remove continuous jobs and assert there is no filter displayed.
    $this->loginAsAdmin();
    $continuous_job->delete();
    $this->drupalGet('admin/tmgmt/jobs');
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->drupalGet('admin/tmgmt/job_items');
    $this->assertNoText(t('Job type'));
    $this->assertNoFieldByName('job_type');

    // Test that the empty text is displayed.
    $this->drupalGet('admin/tmgmt/job_items', array('query' => array('state' => 5)));
    $this->assertText(t('No job items for the current selection.'));
  }


  /**
   * Tests access to add continuous job link.
   */
  public function testAddContinuousLink() {
    $this->drupalLogin($this->createUser(['create translation jobs']));
    $this->drupalGet('admin/tmgmt/jobs');
    $this->assertResponse(200);
    $this->assertNoText('Add continuous job', 'Link is not displayed if user doesn\'t have permission.');
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/tmgmt/jobs');
    $this->assertText('Add continuous job', 'User has access to link with the right permission.');
    \Drupal::service('module_installer')->uninstall(['tmgmt_test']);
    $this->drupalGet('admin/tmgmt/jobs');
    $this->assertNoText('Add continuous job', 'Link is not showing if there is no continuous translator.');
    // The 'Add continuous job' is currently not showing up without clearing the
    // cache after we add a continuous translator.
    // @see https://www.drupal.org/node/2685445
    // \Drupal::service('module_installer')->install(['tmgmt_test']);
    // $this->drupalGet('admin/tmgmt/jobs');
    // $this->assertText('Add continuous job', 'Link is showing if there is a continuous translator.');
  }

}
