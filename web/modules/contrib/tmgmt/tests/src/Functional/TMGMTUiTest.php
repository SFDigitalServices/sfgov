<?php

namespace Drupal\Tests\tmgmt\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\Translator;
use Drupal\filter\Entity\FilterFormat;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;

/**
 * Verifies basic functionality of the user interface
 *
 * @group tmgmt
 */
class TMGMTUiTest extends TMGMTTestBase {
  use TmgmtEntityTestTrait;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $filtered_html_format = FilterFormat::create(array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
    ));
    $filtered_html_format->save();

    $this->addLanguage('de');
    $this->addLanguage('es');
    $this->addLanguage('el');

    // Login as translator only with limited permissions to run these tests.
    $this->loginAsTranslator(array(
      'access administration pages',
      'create translation jobs',
      'submit translation jobs',
      $filtered_html_format->getPermissionName(),
    ), TRUE);
    $this->drupalPlaceBlock('system_breadcrumb_block');

    $this->createNodeType('page', 'Page', TRUE);
    $this->createNodeType('article', 'Article', TRUE);
  }

  /**
   * Test the page callbacks to create jobs and check them out.
   *
   * This includes
   * - Varying checkout situations with form detail values.
   * - Unsupported checkout situations where translator is not available.
   * - Exposed filters for job overview
   * - Deleting a job
   *
   * @todo Separate the exposed filter admin overview test.
   */
  function testCheckoutForm() {
    // Add a first item to the job. This will auto-create the job.
    $job = tmgmt_job_match_item('en', '');
    $job->addItem('test_source', 'test', 1);

    // Go to checkout form.
    $this->drupalGet($job->toUrl());

    // Test primary buttons.
    $this->assertRaw('Save job" class="button js-form-submit form-submit"');

    // Check checkout form.
    $this->assertText('test_source:test:1');

    // Assert that the messages element is not shown.
    $this->assertNoText('Translation Job messages');
    $this->assertNoText('Checkout progress');

    // Add two more job items.
    $job->addItem('test_source', 'test', 2);
    $job->addItem('test_source', 'test', 3);

    // Go to checkout form.
    $this->drupalGet($job->toUrl());

    // Check checkout form.
    $this->assertText('test_source:test:1');
    $this->assertText('test_source:test:2');
    $this->assertText('test_source:test:3');

    // @todo: Test ajax functionality.

    // Attempt to translate into greek.
    $edit = array(
      'target_language' => 'el',
      'settings[action]' => 'translate',
    );
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $this->assertText(t('@translator can not translate from @source to @target.', array('@translator' => 'Test provider', '@source' => 'English', '@target' => 'Greek')));

    // Job still needs to be in state new.
    /** @var \Drupal\tmgmt\JobInterface $job */
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
    $this->assertTrue($job->isUnprocessed());

    // The owner must be the one that submits the job.
    $this->assertTrue($job->isAuthor());
    $this->drupalLogin($this->translator_user);
    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());

    $edit = array(
      'target_language' => 'es',
      'settings[action]' => 'translate',
    );
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    /** @var \Drupal\tmgmt\JobInterface $job */
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
    $this->assertTrue($job->isAuthor());

    // Job needs to be in state active.
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
    $this->assertTrue($job->isActive());
    foreach ($job->getItems() as $job_item) {
      /* @var $job_item \Drupal\tmgmt\JobItemInterface */
      $this->assertTrue($job_item->isNeedsReview());
    }
    $this->assertText(t('Test translation created'));
    $this->assertNoText(t('Test provider called'));

    // Test redirection.
    $this->assertText(t('Job overview'));

    // Another job.
    $previous_tjid = $job->id();
    $job = tmgmt_job_match_item('en', '');
    $job->addItem('test_source', 'test', 9);
    $this->assertNotEqual($job->id(), $previous_tjid);

    // Go to checkout form.
    $this->drupalGet($job->toUrl());

     // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:9');

    $edit = array(
      'target_language' => 'es',
      'settings[action]' => 'submit',
    );
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $this->assertText(t('Test submit'));
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
    $this->assertTrue($job->isActive());

    // Another job.
    $job = tmgmt_job_match_item('en', 'es');
    $item10 = $job->addItem('test_source', 'test', 10);

    // Go to checkout form.
    $this->drupalGet($job->toUrl());

     // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:10');

    $edit = array(
      'settings[action]' => 'reject',
    );
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $this->assertText(t('This is not supported'));
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
    $this->assertTrue($job->isRejected());

    // Check displayed job messages.
    $args = array('@view' => 'view-tmgmt-job-messages');
    $this->assertEqual(2, count($this->xpath('//div[contains(@class, @view)]//tbody/tr', $args)));

    // Check that the author for each is the current user.
    $message_authors = $this->xpath('//div[contains(@class, @view)]//td[contains(@class, @field)]/*[self::a or self::span]  ', $args + array('@field' => 'views-field-name'));
    $this->assertEqual(2, count($message_authors));
    foreach ($message_authors as $message_author) {
      $this->assertEqual($message_author->getText(), $this->translator_user->getDisplayName());
    }

    // Make sure that rejected jobs can be re-submitted.
    $this->assertTrue($job->isSubmittable());
    $edit = array(
      'settings[action]' => 'translate',
    );
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $this->assertText(t('Test translation created'));

    // Now that this job item is in the reviewable state, test primary buttons.
    $this->drupalGet('admin/tmgmt/items/' . $item10->id());
    $this->assertRaw('Save" class="button js-form-submit form-submit"');
    $this->drupalPostForm(NULL, NULL, t('Save'));
    $this->clickLink('View');
    $this->assertRaw('Save as completed" class="button button--primary js-form-submit form-submit"');
    $this->drupalPostForm(NULL, NULL, t('Save'));
    $this->assertRaw('Save job" class="button button--primary js-form-submit form-submit"');
    $this->drupalPostForm(NULL, NULL, t('Save job'));

    // HTML tags count.
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'title' => array(
        'deep_nesting' => array(
          '#text' => '<p><em><strong>Six dummy HTML tags in the title.</strong></em></p>',
          '#label' => 'Title',
        ),
      ),
      'body' => array(
        'deep_nesting' => array(
          '#text' => '<p>Two dummy HTML tags in the body.</p>',
          '#label' => 'Body',
        )
      ),
      'phantom' => array(
        'deep_nesting' => array(
          '#text' => 'phantom text',
          '#label' => 'phantom label',
          '#translate' => FALSE,
          '#format' => 'filtered_html',
        ),
      ),
    ));
    $item4 = $job->addItem('test_source', 'test', 4);
    // Manually active the item as the test expects that.
    $item4->active();
    $this->drupalGet('admin/tmgmt/items/' . $item4->id());
    // Test if the phantom wrapper is not displayed because of #translate FALSE.
    $this->assertNoRaw('tmgmt-ui-element-phantom-wrapper');

    $this->drupalGet('admin/tmgmt/jobs');

    // Total number of tags should be 8 for this job.
    $rows = $this->xpath('//table[@class="views-table views-view-table cols-10"]/tbody/tr');
    $found = FALSE;
    foreach ($rows as $row) {
      if (trim($row->find('css', 'td:nth-child(2)')->getText()) == 'test_source:test:10') {
        $found = TRUE;
        $this->assertEquals(8, $row->find('css', 'td:nth-child(8)')->getText());
      }
    }
    $this->assertTrue($found);

    // Another job.
    $job = tmgmt_job_match_item('en', 'es');
    $job->addItem('test_source', 'test', 11);

    // Go to checkout form.
    $this->drupalGet($job->toUrl());

     // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:11');

    $edit = array(
      'settings[action]' => 'fail',
    );
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $this->assertText(t('Service not reachable'));
    \Drupal::entityTypeManager()->getStorage('tmgmt_job')->resetCache();
    $job = Job::load($job->id());
    $this->assertTrue($job->isUnprocessed());

    // Verify that we are still on the form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');

    // Another job.
    $job = tmgmt_job_match_item('en', 'es');
    $job->addItem('test_source', 'test', 12);

    // Go to checkout form.
    $this->drupalGet($job->toUrl());

    // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:12');

    $edit = array(
      'settings[action]' => 'not_translatable',
    );
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    // @todo Update to correct failure message.
    $this->assertText(t('Fail'));
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
    $this->assertTrue($job->isUnprocessed());

    // Test default settings.
    $this->default_translator->setSetting('action', 'reject');
    $this->default_translator->save();
    $job = tmgmt_job_match_item('en', 'es');
    $job->addItem('test_source', 'test', 13);

    // Go to checkout form.
    $this->drupalGet($job->toUrl());

     // Check checkout form.
    $this->assertText('You can provide a label for this job in order to identify it easily later on.');
    $this->assertText('test_source:test:13');

    // The action should now default to reject.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));
    $this->assertText(t('This is not supported.'));
    $job4 = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
    $this->assertTrue($job4->isRejected());

    $this->drupalGet('admin/tmgmt/jobs');

    // Test if sources languages are correct.
    $sources = $this->xpath('//table[@class="views-table views-view-table cols-10"]/tbody/tr/td[@class="views-field views-field-source-language-1"][contains(., "English")]');
    $this->assertEqual(count($sources), 4);

    // Test if targets languages are correct.
    $targets = $this->xpath('//table[@class="views-table views-view-table cols-10"]/tbody/tr/td[@class="views-field views-field-target-language"][contains(., "Spanish") or contains(., "German")]');
    $this->assertEqual(count($targets), 4);

    // Check that the first action is 'manage'.
    $first_action = $this->xpath('//tbody/tr[2]/td[10]/div/div/ul/li[1]/a');
    $this->assertEqual($first_action[0]->getText(), 'Manage');

    // Test for Unavailable/Unconfigured Translators.
    $this->default_translator->setSetting('action', 'not_translatable');
    $this->default_translator->save();
    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());
    $this->drupalPostForm(NULL, array('target_language' => 'de'), t('Submit to provider'));
    $this->assertText(t('Test provider can not translate from English to German.'));

    // Test for Unavailable/Unconfigured Translators.
    $this->default_translator->setSetting('action', 'not_available');
    $this->default_translator->save();
    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());
    $this->assertText(t('Test provider is not available. Make sure it is properly configured.'));
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));
    $this->assertText(t('@translator is not available. Make sure it is properly configured.', array('@translator' => 'Test provider')));

    // Login as administrator to delete a job.
    $this->loginAsAdmin();
    $this->drupalGet('admin/tmgmt/jobs', array('query' => array(
      'state' => 'All',
    )));

    // Translated languages should now be listed as Needs review.
    $start_rows = $this->xpath('//tbody/tr');
    $this->assertEqual(count($start_rows), 4);
    $this->drupalGet($job4->toUrl('delete-form'));
    $this->assertText('Are you sure you want to delete the translation job test_source:test:11 and 2 more?');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->drupalGet('admin/tmgmt/jobs', array('query' => array(
      'state' => 'All',
    )));
    $end_rows = $this->xpath('//tbody/tr');
    $this->assertEqual(count($end_rows), 3);
    $this->drupalGet('admin/tmgmt/items/' . $item4->id());
    $this->clickLink('Abort');
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    $this->assertText('Aborted');
    $this->assertNoLink('Abort');
  }

  /**
   * Tests the tmgmt_job_checkout() function.
   */
  function testCheckoutFunction() {
    $job = $this->createJob();

    /** @var \Drupal\tmgmt\JobCheckoutManager $job_checkout_manager */
    $job_checkout_manager = \Drupal::service('tmgmt.job_checkout_manager');

    // Check out a job when only the test translator is available. That one has
    // settings, so a checkout is necessary.
    $jobs = $job_checkout_manager->checkoutMultiple(array($job));
    $this->assertEqual($job->id(), $jobs[0]->id());
    $this->assertTrue($job->isUnprocessed());
    $job->delete();

    // Hide settings on the test translator.
    $default_translator = Translator::load('test_translator');
    $default_translator
      ->setSetting('expose_settings', FALSE)
      ->save();

    // Create a job but do not save yet, to simulate how this works in the UI.
    $job = tmgmt_job_create('en', 'de', 0, []);

    $jobs = $job_checkout_manager->checkoutMultiple(array($job));
    $this->assertFalse($jobs);
    $this->assertTrue($job->isActive());

    // A job without target (not specified) language needs to be checked out.
    $job = $this->createJob('en', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $jobs = $job_checkout_manager->checkoutMultiple(array($job));
    $this->assertEqual($job->id(), $jobs[0]->id());
    $this->assertTrue($job->isUnprocessed());

    // Create a second file translator. This should check
    // out immediately.
    $job = $this->createJob();

    $second_translator = $this->createTranslator();
    $second_translator
      ->setSetting('expose_settings', FALSE)
      ->save();

    $jobs = $job_checkout_manager->checkoutMultiple(array($job));
    $this->assertEqual($job->id(), $jobs[0]->id());
    $this->assertTrue($job->isUnprocessed());
  }

  /**
   * Tests the UI of suggestions.
   */
  public function testSuggestions() {
    // Prepare a job and a node for testing.
    $job = $this->createJob();
    $job->addItem('test_source', 'test', 1);
    $job->addItem('test_source', 'test', 7);

    // Go to checkout form.
    $this->drupalGet($job->toUrl());

    $this->assertRaw('20');

    // Verify that suggestions are immediately visible.
    $this->assertText('test_source:test_suggestion:1');
    $this->assertText('test_source:test_suggestion:7');
    $this->assertText('Test suggestion for test source 1');
    $this->assertText('Test suggestion for test source 7');

    // Add the second suggestion.
    $edit = array('suggestions_table[2]' => TRUE);
    $this->drupalPostForm(NULL, $edit, t('Add suggestions'));

    // Total word count should now include the added job.
    $this->assertRaw('31');
    // The suggestion for 7 was added, so there should now be a suggestion
    // for the suggestion instead.
    $this->assertNoText('Test suggestion for test source 7');
    $this->assertText('test_source:test_suggestion_suggestion:7');

    // The HTML test source does not provide suggestions, ensure that the
    // suggestions UI does not show up if there are none.
    $job = $this->createJob();
    $job->addItem('test_html_source', 'test', 1);

    $this->drupalGet($job->toUrl());
    $this->assertNoText('Suggestions');
  }

  /**
   * Test the process of aborting and resubmitting the job.
   */
  function testAbortJob() {
    $job = $this->createJob();
    $job->addItem('test_source', 'test', 1);
    $job->addItem('test_source', 'test', 2);
    $job->addItem('test_source', 'test', 3);

    $edit = array(
      'target_language' => 'es',
      'settings[action]' => 'translate',
    );
    $this->drupalPostForm('admin/tmgmt/jobs/' . $job->id(), $edit, t('Submit to provider'));

    // Abort job.
    $this->drupalPostForm('admin/tmgmt/jobs/' . $job->id(), array(), t('Abort job'));
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    $this->assertText('The user ordered aborting the Job through the UI.');
    $this->assertUrl('admin/tmgmt/jobs/' . $job->id());
    // Reload job and check its state.
    \Drupal::entityTypeManager()->getStorage('tmgmt_job')->resetCache();
    $job = Job::load($job->id());
    $this->assertTrue($job->isAborted());
    foreach ($job->getItems() as $item) {
      $this->assertTrue($item->isAborted());
    }

    // Resubmit the job.
    $this->drupalPostForm('admin/tmgmt/jobs/' . $job->id(), array(), t('Resubmit'));
    $this->drupalPostForm(NULL, array(), t('Confirm'));
    // Test for the log message.
    $this->assertRaw(t('This job is a duplicate of the previously aborted job <a href=":url">#@id</a>',
      array(':url' => $job->toUrl()->toString(), '@id' => $job->id())));

    // Load the resubmitted job and check for its status and values.
    $url_parts = explode('/', $this->getUrl());
    $resubmitted_job = Job::load(array_pop($url_parts));

    $this->assertTrue($resubmitted_job->isUnprocessed());
    $this->assertEqual($job->getTranslator()->id(), $resubmitted_job->getTranslator()->id());
    $this->assertEqual($job->getSourceLangcode(), $resubmitted_job->getSourceLangcode());
    $this->assertEqual($job->getTargetLangcode(), $resubmitted_job->getTargetLangcode());
    $this->assertEqual($job->get('settings')->getValue(), $resubmitted_job->get('settings')->getValue());

    // Test if job items were duplicated correctly.
    foreach ($job->getItems() as $item) {
      // We match job items based on "id #" string. This is not that straight
      // forward, but it works as the test source text is generated as follows:
      // Text for job item with type #type and id #id.
      $_items = $resubmitted_job->getItems(array('data' => array('value' => 'id ' . $item->getItemId(), 'operator' => 'CONTAINS')));
      $_item = reset($_items);
      $this->assertNotEqual($_item->getJobId(), $item->getJobId());
      $this->assertEqual($_item->getPlugin(), $item->getPlugin());
      $this->assertEqual($_item->getItemId(), $item->getItemId());
      $this->assertEqual($_item->getItemType(), $item->getItemType());
      // Make sure counts have been recalculated.
      $this->assertTrue($_item->getWordCount() > 0);
      $this->assertTrue($_item->getCountPending() > 0);
      $this->assertEqual($_item->getCountTranslated(), 0);
      $this->assertEqual($_item->getCountAccepted(), 0);
      $this->assertEqual($_item->getCountReviewed(), 0);
    }

    $this->loginAsAdmin();
    // Navigate back to the aborted job and check for the log message.
    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());

    // Assert that the progress is N/A since the job was aborted.
    $element = $this->xpath('//div[@class="view-content"]/table[@class="views-table views-view-table cols-8"]/tbody//tr[1]/td[4]')[0];
    $this->assertEqual($element->getText(), t('Aborted'));
    $this->assertRaw(t('Job has been duplicated as a new job <a href=":url">#@id</a>.',
      array(':url' => $resubmitted_job->toUrl()->toString(), '@id' => $resubmitted_job->id())));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertText('The translation job ' . $resubmitted_job->label() . ' has been deleted.');
    $this->drupalGet('admin/tmgmt/jobs/2/delete');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->drupalGet('admin/tmgmt/jobs/');
    $this->assertText('No jobs available.');

    // Create a translator.
    $translator = $this->createTranslator();

    // Create a job and attach to the translator.
    $job = $this->createJob();
    $job->translator = $translator;
    $job->save();
    $job->setState(Job::STATE_ACTIVE);

    // Add item to the job.
    $job->addItem('test_source', 'test', 1);
    $this->drupalGet('admin/tmgmt/jobs');

    // Try to abort the job and save.
    $this->clickLink(t('Manage'));
    $this->drupalPostForm(NULL, [], t('Abort job'));
    $this->drupalPostForm(NULL, [], t('Confirm'));

    // Go back to the job page.
    $this->drupalGet('admin/tmgmt/jobs', array('query' => array(
      'state' => JobInterface::STATE_ABORTED,
    )));

    // Check that job is aborted now.
    $this->assertJobStateIcon(1, 'Aborted');
  }

  /**
   * Test the cart functionality.
   */
  function testCart() {

    $this->addLanguage('fr');
    $job_items = array();
    // Create a few job items and add them to the cart.
    for ($i = 1; $i < 6; $i++) {
      $job_item = tmgmt_job_item_create('test_source', 'test', $i);
      $job_item->save();
      $job_items[$i] = $job_item;
    }

    $this->loginAsTranslator();
    foreach ($job_items as $job_item) {
      $this->drupalGet('tmgmt-add-to-cart/' . $job_item->id());
    }

    // Check if the items are displayed in the cart.
    $this->drupalGet('admin/tmgmt/cart');
    foreach ($job_items as $job_item) {
      $this->assertText($job_item->label());
    }

    // Test the remove items from cart functionality.
    $this->drupalPostForm(NULL, [
      'items[1]' => TRUE,
      'items[2]' => FALSE,
      'items[3]' => FALSE,
      'items[4]' => TRUE,
      'items[5]' => FALSE,
    ], t('Remove selected item'));
    $this->assertText($job_items[2]->label());
    $this->assertText($job_items[3]->label());
    $this->assertText($job_items[5]->label());
    $this->assertNoText($job_items[1]->label());
    $this->assertNoText($job_items[4]->label());
    $this->assertText(t('Job items were removed from the cart.'));

    // Test that removed job items from cart were deleted as well.
    $existing_items = JobItem::loadMultiple();
    $this->assertTrue(!isset($existing_items[$job_items[1]->id()]));
    $this->assertTrue(!isset($existing_items[$job_items[4]->id()]));


    $this->drupalPostForm(NULL, array(), t('Empty cart'));
    $this->assertNoText($job_items[2]->label());
    $this->assertNoText($job_items[3]->label());
    $this->assertNoText($job_items[5]->label());
    $this->assertText(t('All job items were removed from the cart.'));

    // No remaining job items.
    $existing_items = JobItem::loadMultiple();
    $this->assertTrue(empty($existing_items));

    $language_sequence = array('en', 'en', 'fr', 'fr', 'de', 'de');
    for ($i = 1; $i < 7; $i++) {
      $job_item = tmgmt_job_item_create('test_source', 'test', $i);
      $job_item->save();
      $job_items[$i] = $job_item;
      $languages[$job_items[$i]->id()] = $language_sequence[$i - 1];
    }
    \Drupal::state()->set('tmgmt.test_source_languages', $languages);
    foreach ($job_items as $job_item) {
      $this->drupalGet('tmgmt-add-to-cart/' . $job_item->id());
    }

    $this->drupalPostForm('admin/tmgmt/cart', array(
      'items[' . $job_items[1]->id() . ']' => TRUE,
      'items[' . $job_items[2]->id() . ']' => TRUE,
      'items[' . $job_items[3]->id() . ']' => TRUE,
      'items[' . $job_items[4]->id() . ']' => TRUE,
      'items[' . $job_items[5]->id() . ']' => TRUE,
      'items[' . $job_items[6]->id() . ']' => FALSE,
      'target_language[]' => array('en', 'de'),
    ), t('Request translation'));

    $this->assertText(t('@count jobs need to be checked out.', array('@count' => 4)));

    // We should have four jobs with following language combinations:
    // [fr, fr] => [en]
    // [de] => [en]
    // [en, en] => [de]
    // [fr, fr] => [de]

    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_job');
    $jobs = $storage->loadByProperties(['source_language' => 'fr', 'target_language' => 'en']);
    $job = reset($jobs);
    $this->assertEquals(2, count($job->getItems()));

    $jobs = $storage->loadByProperties(['source_language' => 'de', 'target_language' => 'en']);
    $job = reset($jobs);
    $this->assertEquals(1, count($job->getItems()));

    $jobs = $storage->loadByProperties(['source_language' => 'en', 'target_language' => 'de']);
    $job = reset($jobs);
    $this->assertEquals(2, count($job->getItems()));

    $jobs = $storage->loadByProperties(['source_language' => 'fr', 'target_language' => 'de']);
    $job = reset($jobs);
    $this->assertEquals(2, count($job->getItems()));

    $this->drupalGet('admin/tmgmt/cart');
    // Both fr and one de items must be gone.
    $this->assertNoText($job_items[1]->label());
    $this->assertNoText($job_items[2]->label());
    $this->assertNoText($job_items[3]->label());
    $this->assertNoText($job_items[4]->label());
    $this->assertNoText($job_items[5]->label());
    // One de item is in the cart as it was not selected for checkout.
    $this->assertText($job_items[6]->label());

    // Check to see if no items are selected and the error message pops up.
    $this->drupalPostForm('admin/tmgmt/cart', ['items[' . $job_items[6]->id() . ']' => FALSE], t('Request translation'));
    $this->assertUniqueText(t("You didn't select any source items."));
  }

  /**
   * Test titles of various TMGMT pages.
   *
   * @todo Miro wants to split this test to specific tests (check)
   */
  function testPageTitles() {
    $this->loginAsAdmin();
    $translator = $this->createTranslator();
    $job = $this->createJob();
    $job->translator = $translator;
    $job->settings = array();
    $job->save();
    $item = $job->addItem('test_source', 'test', 1);

    // Tmgtm settings.
    $this->drupalGet('/admin/tmgmt/settings');
    $this->assertTitle(t('Settings | Drupal'));
    // Manage translators.
    $this->drupalGet('/admin/tmgmt/translators');
    $this->assertTitle(t('Providers | Drupal'));
    // Add Translator.
    $this->drupalGet('/admin/tmgmt/translators/add');
    $this->assertTitle(t('Add Provider | Drupal'));
    // Delete Translators.
    $this->drupalGet('/admin/tmgmt/translators/manage/' . $translator->id() . '/delete');
    $this->assertTitle(t('Are you sure you want to delete the provider @label? | Drupal', ['@label' => $translator->label()]));
    // Edit Translators.
    $this->drupalGet('/admin/tmgmt/translators/manage/' . $translator->id());
    $this->assertTitle(t('Edit provider | Drupal'));
    // Delete Job.
    $this->drupalGet('/admin/tmgmt/jobs/' . $job->id() . '/delete');
    $this->assertTitle(t('Are you sure you want to delete the translation job @label? | Drupal', ['@label' => $job->label()]));
    // Resubmit Job.
    $this->drupalGet('/admin/tmgmt/jobs/' . $job->id() . '/resubmit');
    $this->assertTitle(t('Resubmit as a new job? | Drupal'));
    // Abort Job.
    $this->drupalGet('/admin/tmgmt/jobs/' . $job->id() . '/abort');
    $this->assertTitle(t('Abort this job? | Drupal'));
    // Edit Job Item.
    $this->drupalGet('/admin/tmgmt/items/' . $job->id());
    $this->assertTitle(t('Job item @label | Drupal', ['@label' => $item->label()]));
    // Assert the breadcrumb.
    $this->assertLink(t('Home'));
    $this->assertLink(t('Administration'));
    $this->assertLink(t('Job overview'));
    $this->assertLink($job->label());
    // Translation Sources.
    $this->drupalGet('admin');
    $this->clickLink(t('Translation'));
    $this->assertTitle(t('Translation | Drupal'));
    $this->clickLink(t('Cart'));
    $this->assertTitle(t('Cart | Drupal'));
    $this->clickLink(t('Jobs'));
    $this->assertTitle(t('Job overview | Drupal'));
    $this->clickLink(t('Sources'));
    $this->assertTitle(t('Translation Sources | Drupal'));
  }

  /**
   * Test the deletion and abortion of job item.
   *
   * @todo There will be some overlap with Aborting items & testAbortJob.
   */
  function testJobItemDelete() {
    $this->loginAsAdmin();

    // Create a translator.
    $translator = $this->createTranslator();
    // Create a job and attach to the translator.
    $job = $this->createJob();
    $job->translator = $translator;
    $job->settings = array();
    $job->save();
    $job->setState(Job::STATE_ACTIVE);

    // Add item to the job.
    $item = $job->addItem('test_source', 'test', 1);
    $item->setState(JobItem::STATE_ACTIVE);

    // Check that there is no delete link on item review form.
    $this->drupalGet('admin/tmgmt/items/' . $item->id());
    $this->assertNoFieldById('edit-delete', NULL, 'There is no delete button.');

    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());

    // Check that there is no delete link.
    $this->assertNoLink('Delete');

    // Check for abort link.
    $this->assertLink('Abort');

    $this->clickLink('Abort');
    $this->assertText(t('Are you sure you want to abort the job item test_source:test:1?'));

    // Check if cancel button is present or not.
    $this->assertLink('Cancel');

    // Abort the job item.
    $this->drupalPostForm(NULL, [], t('Confirm'));

    // Reload job and check its state and state of its item.
    \Drupal::entityTypeManager()->getStorage('tmgmt_job')->resetCache();
    $job = Job::load($job->id());
    $this->assertTrue($job->isFinished());
    $items = $job->getItems();
    $item = reset($items);
    $this->assertTrue($item->isAborted());

    // Check that there is no delete button on item review form.
    $this->drupalGet('admin/tmgmt/items/' . $item->id());
    $this->assertNoFieldById('edit-delete', NULL, 'There is delete button.');

    // Check that there is no accept button on item review form.
    $this->assertNoFieldById('edit-accept', NULL, 'There is no accept button.');

    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());

    // Check that there is no delete link on job overview.
    $this->assertNoLink('Delete');
  }

  /**
   * Test the settings of TMGMT.
   *
   * @todo some settings have no test coverage in their effect.
   * @todo we will need to switch them in context of the other lifecycle tests.
   */
  public function testSettings() {
    $this->loginAsAdmin();

    $settings = \Drupal::config('tmgmt.settings');
    $this->assertTrue($settings->get('quick_checkout'));
    $this->assertTrue($settings->get('anonymous_access'));
    $this->assertEqual('_never', $settings->get('purge_finished'));
    $this->assertTrue($settings->get('word_count_exclude_tags'));
    $this->assertEqual(20, $settings->get('source_list_limit'));
    $this->assertEqual(50, $settings->get('job_items_cron_limit'));
    $this->assertTrue($settings->get('respect_text_format'));
    $this->assertFalse($settings->get('submit_job_item_on_cron'));

    $this->drupalGet('admin/tmgmt/settings');
    $edit = [
      'tmgmt_quick_checkout' => FALSE,
      'tmgmt_anonymous_access' => FALSE,
      'tmgmt_purge_finished' => 0,
      'respect_text_format' => FALSE,
      'tmgmt_submit_job_item_on_cron' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $settings = \Drupal::config('tmgmt.settings');
    $this->assertFalse($settings->get('quick_checkout'));
    $this->assertFalse($settings->get('anonymous_access'));
    $this->assertEqual(0, $settings->get('purge_finished'));
    $this->assertFalse($settings->get('respect_text_format'));
    $this->assertTrue($settings->get('submit_job_item_on_cron'));
  }

  /**
   * Tests of the job item review process.
   */
  public function testProgress() {
    // Test that there are no jobs at the beginning.
    $this->drupalGet('admin/tmgmt/jobs');
    $this->assertText('No jobs available.');
    $this->assertOptionByText('edit-state', 'Items - In progress');
    $this->assertOptionByText('edit-state', 'Items - Needs review');
    $this->assertOptionByText('edit-state', 'Items - Translation is requested from the elders of the Internet');

    // Make sure the legend label is displayed for the test translator state.
    $this->assertText('Translation is requested from the elders of the Internet');
    $this->drupalGet('admin/tmgmt/sources');

    // Create Jobs.
    $job1 = $this->createJob();
    $job1->save();
    $job1->setState(Job::STATE_UNPROCESSED);

    $job2 = $this->createJob();
    $job2->save();
    $job2->setState(Job::STATE_ACTIVE);

    $job3 = $this->createJob();
    $job3->save();
    $job3->setState(Job::STATE_REJECTED);

    $job4 = $this->createJob();
    $job4->save();
    $job4->setState(Job::STATE_ABORTED);

    $job5 = $this->createJob();
    $job5->save();
    $job5->setState(Job::STATE_FINISHED);

    // Test their icons.
    $this->drupalGet('admin/tmgmt/jobs', array('query' => array(
      'state' => 'All',
    )));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 5);
    $this->assertJobStateIcon(1, 'Unprocessed');
    $this->assertJobStateIcon(2, 'In progress');
    $this->assertJobStateIcon(3, 'Rejected');
    $this->assertJobStateIcon(4, 'Aborted');
    $this->assertJobStateIcon(5, 'Finished');

    // Test the row amount for each state selected.
    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => 'open_jobs']]);
    $this->assertEqual(count($this->xpath('//tbody/tr')), 3);

    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => JobInterface::STATE_UNPROCESSED]]);
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);

    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => JobInterface::STATE_REJECTED]]);
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);

    $this->drupalGet('admin/tmgmt/jobs', array('query' => array('state' => JobInterface::STATE_ABORTED)));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);

    $this->drupalGet('admin/tmgmt/jobs', array('query' => array('state' => JobInterface::STATE_FINISHED)));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);

    \Drupal::state()->set('tmgmt.test_source_data', array(
      'title' => array(
        'deep_nesting' => array(
          '#text' => '<p><em><strong>Six dummy HTML tags in the title.</strong></em></p>',
          '#label' => 'Title',
        ),
      ),
      'body' => array(
        'deep_nesting' => array(
          '#text' => '<p>Two dummy HTML tags in the body.</p>',
          '#label' => 'Body',
        )
      ),
    ));

    // Add 2 items to job1 and submit it to provider.
    $item1 = $job1->addItem('test_source', 'test', 1);
    $job1->addItem('test_source', 'test', 2);
    $this->drupalGet('admin/tmgmt/job_items', array('query' => array('state' => 'All')));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);
    $this->assertJobItemOverviewStateIcon(1, 'Inactive');
    $this->assertLink($job1->label());
    $this->drupalGet($job1->toUrl());
    $edit = array(
      'target_language' => 'de',
      'settings[action]' => 'submit',
    );
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));

    // Translate body of one item.
    $this->drupalGet('admin/tmgmt/items/' . $item1->id());
    $this->drupalPostForm(NULL, array('body|deep_nesting[translation]' => 'translation'), t('Save'));
    // Check job item state is still in progress.
    $this->assertJobItemStateIcon(1, 'In progress');
    $this->drupalGet('admin/tmgmt/job_items', array('query' => array('state' => JobItemInterface::STATE_ACTIVE)));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);
    $this->assertJobItemOverviewStateIcon(1, 'In progress');
    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => 'job_item_' . JobItemInterface::STATE_ACTIVE]]);
    // Check progress bar and icon.
    $this->assertJobProgress(1, 3, 1, 0, 0);
    $this->assertJobStateIcon(1, 'In progress');

    // Set the translator status to tmgmt_test_generating.
    \Drupal::entityTypeManager()->getStorage('tmgmt_job_item')->resetCache();
    $item1 = JobItem::load($item1->id());
    $item1->setTranslatorState('tmgmt_test_generating');
    $item1->save();

    $this->drupalGet('admin/tmgmt/job_items', array('query' => array('state' => 'tmgmt_test_generating')));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);
    $this->assertJobItemOverviewStateIcon(1, 'Translation is requested from the elders of the Internet');
    $this->assertRaw('earth.svg"');
    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => 'job_item_' . JobItemInterface::STATE_ACTIVE]]);
    $this->assertJobProgress(1, 3, 1, 0, 0);
    $this->assertJobStateIcon(1, 'Translation is requested from the elders of the Internet');
    $this->assertRaw('earth.svg"');
    // Also check the translator state.
    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => 'job_item_tmgmt_test_generating']]);
    $this->assertJobProgress(1, 3, 1, 0, 0);
    $this->assertJobStateIcon(1, 'Translation is requested from the elders of the Internet');
    $this->assertRaw('earth.svg"');


    // Translate title of one item.
    $this->drupalGet('admin/tmgmt/items/' . $item1->id());
    $this->drupalPostForm(NULL, array('title|deep_nesting[translation]' => 'translation'), t('Save'));
    // Check job item state changed to needs review.
    $this->assertJobItemStateIcon(1, 'Needs review');
    $this->drupalGet('admin/tmgmt/job_items', array('query' => array('state' => JobItemInterface::STATE_REVIEW)));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);
    $this->assertJobItemOverviewStateIcon(1, 'Needs review');

    // Check exposed filter for needs review.
    $this->drupalGet('admin/tmgmt/jobs', ['query' => ['state' => 'job_item_' . JobItemInterface::STATE_REVIEW]]);
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);
    // Check progress bar and icon.
    $this->assertJobProgress(1, 2, 2, 0, 0);
    $this->assertJobStateIcon(1, 'Needs review');

    // Review the translation one by one.
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/tmgmt/items/' . $item1->id());
    $page->pressButton('reviewed-body|deep_nesting');
    $this->drupalGet('admin/tmgmt/jobs/' . $job1->id());
    // Check the icon of the job item.
    $this->assertJobItemStateIcon(1, 'Needs review');

    $this->drupalGet('admin/tmgmt/items/' . $item1->id());;
    $page->pressButton('reviewed-title|deep_nesting');
    $this->drupalGet('admin/tmgmt/jobs/' . $job1->id());
    // Check the icon of the job item.
    $this->assertJobItemStateIcon(1, 'Needs review');
    $this->drupalGet('admin/tmgmt/jobs', array('query' => array('state' => 'open_jobs')));
    // Check progress bar and icon.
    $this->assertJobProgress(1, 2, 0, 2, 0);
    $this->assertJobStateIcon(1, 'Needs review');

    // Save one job item as completed.
    $this->drupalPostForm('admin/tmgmt/items/' . $item1->id(), NULL, t('Save as completed'));
    // Check job item state changed to accepted.
    $this->assertJobItemStateIcon(1, 'Accepted');
    $this->drupalGet('admin/tmgmt/job_items', array('query' => array('state' => JobItemInterface::STATE_ACCEPTED)));
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);
    $this->assertJobItemOverviewStateIcon(1, 'Accepted');
    $this->drupalGet('admin/tmgmt/jobs', array('query' => array('state' => 'open_jobs')));
    // Check progress bar and icon.
    $this->assertJobProgress(1, 2, 0, 0, 2);
    $this->assertJobStateIcon(1, 'In progress');

    // Assert the legend.
    $this->drupalGet('admin/tmgmt/items/' . $item1->id());
    $this->assertRaw('class="tmgmt-color-legend');
  }

  /**
   * Asserts task item progress bar.
   *
   * @param int $row
   *   The row of the item you want to check.
   * @param int $state
   *   The expected state.
   */
  private function assertJobStateIcon($row, $state) {
    if ($state == 'Unprocessed' || $state == 'Rejected' || $state == 'Aborted' || $state == 'Finished') {
      $result = $this->xpath('//table/tbody/tr[' . $row . ']/td[6]')[0];
      $this->assertEqual(trim($result->getHtml()), $state);
    }
    else {
      $result = $this->xpath('//table/tbody/tr[' . $row . ']/td[1]/img')[0];
      $this->assertEqual($result->getAttribute('title'), $state);
    }
  }

  /**
   * Asserts task item progress bar.
   *
   * @param int $row
   *   The row of the item you want to check.
   * @param int $state
   *   The expected state.
   *
   */
  protected function assertJobItemStateIcon($row, $state) {
    if ($state == 'Inactive' || $state == 'Aborted' || $state == 'Accepted') {
      $result = $this->xpath('//div[@id="edit-job-items-wrapper"]//tbody/tr[' . $row . ']/td[4]')[0];
      $this->assertEqual(trim($result->getHtml()), $state);
    }
    else {
      $result = $this->xpath('//div[@id="edit-job-items-wrapper"]//tbody/tr[' . $row . ']/td[1]/img')[0];
      $this->assertEqual($result->getAttribute('title'), $state);
    }
  }

  /**
   * Asserts job item overview progress bar.
   *
   * @param int $row
   *   The row of the item you want to check.
   * @param int $state
   *   The expected state.
   *
   */
  private function assertJobItemOverviewStateIcon($row, $state) {
    if ($state == 'Inactive' || $state == 'Aborted' || $state == 'Accepted') {
      $result = $this->xpath('//table/tbody/tr[' . $row . ']/td[7]')[0];
      $this->assertEqual(trim($result->getHtml()), $state);
    }
    else {
      $result = $this->xpath('//table/tbody/tr[' . $row . ']/td[1]/img')[0];
      $this->assertEqual($result->getAttribute('title'), $state);
    }
  }


  /**
   * Asserts task item progress bar.
   *
   * @param int $row
   *   The row of the item you want to check.
   * @param int $pending
   *   The amount of pending items.
   * @param int $reviewed
   *   The amount of reviewed items.
   * @param int $translated
   *   The amount of translated items.
   * @param int $accepted
   *   The amount of accepted items.
   *
   */
  private function assertJobProgress($row, $pending, $translated, $reviewed, $accepted) {
    $result = $this->xpath('//table/tbody/tr[' . $row . ']/td[6]')[0];
    $div_number = 1;
    if ($pending > 0) {
      $this->assertEquals('tmgmt-progress tmgmt-progress-pending', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
      $div_number++;
    }
    else {
      $this->assertNotEquals('tmgmt-progress tmgmt-progress-pending', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
    }
    if ($translated > 0) {
      $this->assertEquals('tmgmt-progress tmgmt-progress-translated', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
      $div_number++;
    }
    else {
      $child = $result->find('css', "div > div:nth-child($div_number)");
      $this->assertTrue(!$child || $child->getAttribute('class') != 'tmgmt-progress tmgmt-progress-translated');
    }
    if ($reviewed > 0) {
      $this->assertEquals('tmgmt-progress tmgmt-progress-reviewed', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
      $div_number++;
    }
    else {
      $child = $result->find('css', "div > div:nth-child($div_number)");
      $this->assertTrue(!$child || $child->getAttribute('class') != 'tmgmt-progress tmgmt-progress-reviewed');
    }
    if ($accepted > 0) {
      $this->assertEquals('tmgmt-progress tmgmt-progress-accepted', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
    }
    else {
      $child = $result->find('css', "div > div:nth-child($div_number)");
      $this->assertTrue(!$child || $child->getAttribute('class') != 'tmgmt-progress tmgmt-progress-accepted');
    }
    $title = t('Pending: @pending, translated: @translated, reviewed: @reviewed, accepted: @accepted.', array(
      '@pending' => $pending,
      '@translated' => $translated,
      '@reviewed' => $reviewed,
      '@accepted' => $accepted,
    ));
    $this->assertEquals($title, $result->find('css', 'div')->getAttribute('title'));
  }

}
