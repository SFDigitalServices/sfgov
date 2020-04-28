<?php

namespace Drupal\Tests\tmgmt_local\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt_local\Entity\LocalTask;

/**
 * Basic tests for the local translator.
 *
 * @group tmgmt
 */
class LocalTranslatorTest extends LocalTranslatorTestBase {

  /**
   * Test assignee skills.
   */
  public function testAssigneeSkillsForTasks() {

    $this->addLanguage('fr');

    $assignee1 = $this->drupalCreateUser($this->localTranslatorPermissions);
    $this->drupalLogin($assignee1);
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'en',
    );
    $this->drupalPostForm('user/' . $assignee1->id() . '/edit', $edit, t('Save'));
    $this->assertText('The \'from\' and \'to\' language fields can\'t have the same value.');
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $assignee1->id() . '/edit', $edit, t('Save'));
    $this->assertText('The changes have been saved.');
    $edit = array(
      'tmgmt_translation_skills[1][language_from]' => 'en',
      'tmgmt_translation_skills[1][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $assignee1->id() . '/edit', $edit, t('Save'));
    $this->assertText('The language combination has to be unique.');

    $assignee2 = $this->drupalCreateUser($this->localTranslatorPermissions);
    $this->drupalLogin($assignee2);
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $assignee2->id() . '/edit', $edit, t('Save'));
    $edit = array(
      'tmgmt_translation_skills[1][language_from]' => 'de',
      'tmgmt_translation_skills[1][language_to]' => 'en',
    );
    $this->drupalPostForm('user/' . $assignee2->id() . '/edit', $edit, t('Save'));

    $assignee3 = $this->drupalCreateUser($this->localTranslatorPermissions);
    $this->drupalLogin($assignee3);
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $assignee3->id() . '/edit', $edit, t('Save'));
    $edit = array(
      'tmgmt_translation_skills[1][language_from]' => 'de',
      'tmgmt_translation_skills[1][language_to]' => 'en',
    );
    $this->drupalPostForm('user/' . $assignee3->id() . '/edit', $edit, t('Save'));
    $edit = array(
      'tmgmt_translation_skills[2][language_from]' => 'en',
      'tmgmt_translation_skills[2][language_to]' => 'fr',
    );
    $this->drupalPostForm('user/' . $assignee3->id() . '/edit', $edit, t('Save'));

    $job1 = $this->createJob('en', 'de');
    $job2 = $this->createJob('de', 'en');
    $job3 = $this->createJob('en', 'fr');

    $local_task1 = LocalTask::create(array(
      'uid' => $job1->getOwnerId(),
      'tjid' => $job1->id(),
      'title' => 'Task 1',
    ));
    $local_task1->save();

    $local_task2 = LocalTask::create(array(
      'uid' => $job2->getOwnerId(),
      'tjid' => $job2->id(),
      'title' => 'Task 2',
    ));
    $local_task2->save();

    $local_task3 = LocalTask::create(array(
      'uid' => $job3->getOwnerId(),
      'tjid' => $job3->id(),
      'title' => 'Task 3',
    ));
    $local_task3->save();

    // Test languages involved in tasks.
    $this->assertEqual(
      tmgmt_local_tasks_languages(array(
        $local_task1->id(),
        $local_task2->id(),
        $local_task3->id(),
      )),
      array(
        'en' => array('de', 'fr'),
        'de' => array('en'),
      )
    );

    // Test available translators for task en - de.
    $this->assertEqual(
      tmgmt_local_get_assignees_for_tasks(array($local_task1->id())),
      array(
        $assignee1->id() => $assignee1->getDisplayName(),
        $assignee2->id() => $assignee2->getDisplayName(),
        $assignee3->id() => $assignee3->getDisplayName(),
      )
    );

    // Test available translators for tasks en - de, de - en.
    $this->assertEqual(
      tmgmt_local_get_assignees_for_tasks(array($local_task1->id(), $local_task2->id())),
      array(
        $assignee2->id() => $assignee2->getDisplayName(),
        $assignee3->id() => $assignee3->getDisplayName(),
      )
    );

    // Test available translators for tasks en - de, de - en, en - fr.
    $this->assertEqual(
      tmgmt_local_get_assignees_for_tasks(array(
        $local_task1->id(),
        $local_task2->id(),
        $local_task3->id(),
      )),
      array(
        $assignee3->id() => $assignee3->getDisplayName(),
      )
    );
  }

  /**
   * Test the basic translation workflow.
   */
  public function testBasicWorkflow() {
    $translator = Translator::load('local');

    /** @var FilterFormat $basic_html_format */
    $basic_html_format = FilterFormat::create(array(
      'format' => 'basic_html',
      'name' => 'Basic HTML',
    ));
    $basic_html_format->save();

    // Create a job and request a local translation.
    $this->loginAsTranslator();
    $job = $this->createJob();
    $job->translator = $translator->id();
    $job->addItem('test_source', 'test', '1');
    \Drupal::state()->set('tmgmt.test_source_data', [
      'dummy' => [
        'deep_nesting' => [
          '#text' => file_get_contents(drupal_get_path('module', 'tmgmt') . '/tests/testing_html/sample.html'),
          '#label' => 'Label for job item with type test and id 2.',
          '#translate' => TRUE,
          '#format' => 'basic_html',
        ],
      ],
      'second' => [
        '#text' => 'second text',
        '#label' => 'Second label',
        '#translate' => TRUE,
      ],
      'third' => [
        '#text' => 'third text',
        '#label' => 'Third label',
        '#translate' => TRUE,
      ],
    ]);
    $job->addItem('test_source', 'test', '2');
    $job->save();

    // Make sure that the checkout page works as expected when there are no
    // roles.
    $this->drupalGet($job->toUrl());
    $this->assertText(t('@translator can not translate from @source to @target.', array(
      '@translator' => 'Drupal user',
      '@source' => 'English',
      '@target' => 'German',
    )));
    $element = $this->xpath('//*[@id="edit-translator"]/option')[0];
    $this->assertEqual($element->getText(), 'Drupal user (unsupported)');
    $this->assignee = $this->drupalCreateUser(
      array_merge($this->localTranslatorPermissions, [$basic_html_format->getPermissionName()])
    );

    // The same when there is a single role.
    $this->drupalGet($job->toUrl());
    $this->assertText(t('@translator can not translate from @source to @target.', array(
      '@translator' => 'Drupal user',
      '@source' => 'English',
      '@target' => 'German',
    )));

    // Create another local translator with the required abilities.
    $other_assignee_same = $this->drupalCreateUser($this->localTranslatorPermissions);

    // And test again with two roles but still no abilities.
    $this->drupalGet($job->toUrl());
    $this->assertText(t('@translator can not translate from @source to @target.', array(
      '@translator' => 'Drupal user',
      '@source' => 'English',
      '@target' => 'German',
    )));

    $this->drupalLogin($other_assignee_same);
    // Configure language abilities.
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $other_assignee_same->id() . '/edit', $edit, t('Save'));

    // Check that the user is not listed in the translator selection form.
    $this->loginAsAdmin();
    $this->drupalGet($job->toUrl());
    $element = $this->xpath('//*[@id="edit-translator"]/option')[0];
    $this->assertEqual($element->getText(), 'Drupal user');
    $this->assertText(t('Assign job to'));
    $this->assertText($other_assignee_same->getDisplayName());
    $this->assertNoText($this->assignee->getDisplayName());

    $this->drupalLogin($this->assignee);
    // Configure language abilities.
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $this->assignee->id() . '/edit', $edit, t('Save'));

    // Check that the translator is now listed.
    $this->loginAsAdmin();
    $this->drupalGet($job->toUrl());
    $this->assertText($this->assignee->getDisplayName());

    // Test assign task while submitting job.
    $job_comment = 'Dummy job comment';
    $edit = [
      'settings[translator]' => $this->assignee->id(),
      'settings[job_comment]' => $job_comment,
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));
    $this->drupalLogin($this->assignee);
    $this->drupalGet('translate/pending');
    $this->assertText($job->label());

    $this->loginAsAdmin($this->localManagerPermissions);
    $this->drupalGet('manage-translate/assigned');
    $this->assertNoLink(t('Delete'));
    $this->clickLink(t('Unassign'));
    $this->drupalPostForm(NULL, [], t('Unassign'));

    // Test for job comment in the job checkout info pane.
    $this->drupalGet($job->toUrl());
    $this->assertText($job_comment);

    $this->drupalLogin($this->assignee);

    // Create a second local translator with different language abilities,
    // make sure that he does not see the task.
    $other_translator = $this->drupalCreateUser($this->localTranslatorPermissions);
    $this->drupalLogin($other_translator);
    // Configure language abilities.
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'de',
      'tmgmt_translation_skills[0][language_to]' => 'en',
    );
    $this->drupalPostForm('user/' . $other_translator->id() . '/edit', $edit, t('Save'));
    $this->drupalGet('translate');
    $this->assertNoText($job->label());

    $this->drupalLogin($this->assignee);

    // Check the translate overview.
    $this->drupalGet('translate');
    $this->assertText($job->label());
    // @todo: Fails, encoding problem?
    // $this->assertText(t('@from => @to', array('@from' => 'en', '@to' => 'de')));

    // Test LocalTaskForm.
    $this->clickLink('View');
    $this->assertText('Unassigned');
    $xpath = $this->xpath('//*[@id="edit-status"]');
    $this->assertTrue(empty($xpath));

    $this->loginAsAdmin($this->localManagerPermissions);
    $this->drupalGet('translate');
    $this->clickLink('View');
    $xpath = $this->xpath('//*[@id="edit-tuid"]');
    $this->assertFalse(empty($xpath));
    $edit = array(
      'tuid' => $this->assignee->id(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save task'));
    $this->assertText(t('Assigned to user @assignee.', ['@assignee' => $this->assignee->getDisplayName()]));

    $this->drupalGet('manage-translate/assigned');
    $this->clickLink('View');
    $edit = array(
      'tuid' => 0,
    );
    $this->drupalPostForm(NULL, $edit, t('Save task'));

    $this->drupalLogin($this->assignee);
    $this->drupalGet('translate');

    // Assign to action not working yet.
    $edit = array(
      'tmgmt_local_task_bulk_form[0]' => TRUE,
      'action' => 'tmgmt_local_task_assign_to_me',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText(t('Assign to me was applied to 1 item.'));

    // Unassign again.
    $edit = array(
      'tmgmt_local_task_bulk_form[0]' => TRUE,
      'action' => 'tmgmt_local_task_unassign_multiple',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText(t('Unassign was applied to 1 item.'));

    // Now test the assign link.
    // @todo Action should not redirect to mine.
    $this->drupalGet('translate');
    $this->clickLink(t('Assign to me'));
    $this->assertText(t('The task has been assigned to you.'));

    // Assert created local task and task items.
    $this->drupalGet('translate/pending');
    $this->clickLink(t('View'));
    $this->assertTrue(preg_match('|translate/(\d+)|', $this->getUrl(), $matches), 'Task found');
    /** @var \Drupal\tmgmt_local\Entity\LocalTask $task */
    $task = \Drupal::entityTypeManager()->getStorage('tmgmt_local_task')->load($matches[1]);
    $this->assertTrue($task->isPending());

    $items = $task->getItems();
    /** @var \Drupal\tmgmt_local\Entity\LocalTaskItem $first_task_item */
    $first_task_item = reset($items);
    $this->assertTrue($first_task_item->isPending());

    // Log in with the translator with the same abilities, make sure that he
    // does not see the assigned task.
    $this->drupalLogin($other_assignee_same);
    $this->drupalGet('translate');
    $this->assertNoText($job->label());
    $this->drupalGet('translate/items/' . $first_task_item->id());
    $this->assertResponse(403);

    $this->drupalLogin($this->admin_user);

    // Unassign the task.
    $this->drupalGet('translate/' . $task->id());
    $edit = [
      'tuid' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save task'));
    $this->clickLink(t('View'));

    // Assign again the task to himself.
    $edit = [
      'tuid' => $this->assignee->id(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save task'));

    $this->drupalLogin($this->assignee);

    // Translate the task.
    $this->drupalGet('translate/' . $task->id());
    $this->assertText('test_source:test:1');
    $this->assertText('test_source:test:2');

    // Translate the first item.
    $this->drupalGet($first_task_item->toUrl());

    // Assert the breadcrumb.
    $this->assertLink(t('Home'));
    $this->assertLink(t('Local Tasks'));
    $this->assertText($job->label());

    // Assert the header.
    $this->assertLink($first_task_item->getJobItem()->getSourceLabel());
    $this->assertText($first_task_item->getJobItem()->getSourceType());
    $this->assertText($first_task_item->getJobItem()->getJob()->getSourceLanguage()->getName());
    $this->assertText($first_task_item->getJobItem()->getJob()->getTargetLanguage()->getName());
    $this->assertText(\Drupal::service('date.formatter')->format($first_task_item->getChangedTime()));
    $this->assertText($first_task_item->getStatus());
    $this->assertLink($first_task_item->getTask()->label());

    $this->assertText(t('Dummy'));
    // Check if Save as completed button is displayed.
    $elements = $this->xpath('//*[@id="edit-save-as-completed"]');
    $this->assertTrue(!empty($elements), "'Save as completed' button appears.");

    // Job comment is present in the translate tool as well.
    $this->assertText($job_comment);
    $this->assertText('test_source:test:1');

    // Try to complete a translation when translations are missing.
    $edit = array(
      'dummy|deep_nesting[translation]' => '',
    );
    $this->drupalPostForm(NULL, $edit, t('Save as completed'));
    $this->assertText(t('Missing translation.'));

    $edit = array(
      'dummy|deep_nesting[translation]' => $translation1 = 'German translation of source 1',
    );
    $this->drupalPostForm(NULL, $edit, t('Save as completed'));
    $this->assertRaw('tmgmt/icons/gray-check.svg" title="Translated"');
    $this->assertText('The translation for ' . $first_task_item->label() . ' has been saved as completed.');

    // Check that the source has not being modified.
    $this->clickLink(t('View'));
    /** @var \Drupal\tmgmt\JobItemInterface $job_item */
    $job_items = $job->getItems(['tjiid' => 1]);
    $job_item = reset($job_items);
    $source = $job_item->getData(['dummy', 'deep_nesting', '#text']);
    $this->assertText($source);

    // Review and accept the first item.
    \Drupal::entityTypeManager()->getStorage('tmgmt_job_item')->resetCache();
    drupal_static_reset('tmgmt_local_task_statistics_load');
    /** @var \Drupal\tmgmt\JobItemInterface $item1 */
    $item1 = JobItem::load(1);
    // The first item should be available for review.
    $this->assertTrue($item1->isNeedsReview(), 'Job item 1 needs review.');
    $item1->acceptTranslation();

    // The first item should be accepted now, the second still in progress.
    \Drupal::entityTypeManager()->getStorage('tmgmt_local_task_item')->resetCache();
    $this->drupalGet('translate/1');
    // Checking if the 'Save as completed' button is not displayed.
    $this->drupalGet('translate/items/1');
    $elements = $this->xpath('//*[@id="edit-save-as-completed"]');
    $this->assertTrue(empty($elements), "'Save as completed' button does not appear.");
    // Checking if the item status is not displayed.
    $this->assertNoRaw('title="Finish"');
    $this->assertNoRaw('title="Reject"');

    // We can go back to the Task from the item.
    $this->drupalGet('translate/items/1');
    $this->clickLink($task->label());
    // Let's check the task status.
    /** @var \Drupal\tmgmt_local\Entity\LocalTask $task */
    $task = \Drupal::entityTypeManager()->getStorage('tmgmt_local_task')->loadUnchanged($task->id());
    $this->assertTrue($task->isPending());
    /** @var \Drupal\tmgmt_local\Entity\LocalTaskItem $second_task_item */
    list($first_task_item, $second_task_item) = array_values($task->getItems());
    $this->assertTrue($first_task_item->isClosed());

    // Assert that translator can provide translations for a "Dummy" field. An
    // empty text field should be displayed as translator does not have a
    // permission to use "full_html" text format.
    $second_task_item->updateData('dummy|deep_nesting', ['#format' => 'full_html']);
    $second_task_item->save();
    $this->clickLink(t('Translate'));
    $this->assertFieldByName('dummy|deep_nesting[translation]');
    $this->assertRaw('Save as completed" class="button button--primary js-form-submit form-submit"');
    $translation_field = $this->xpath('//*[@id="edit-dummydeep-nesting-translation"]')[0];
    $this->assertEqual($translation_field->getText(), '');

    // Translate the second item but do not mark as translated it yet.
    $second_task_item->updateData('dummy|deep_nesting', ['#format' => 'basic_html']);
    $second_task_item->save();
    $this->drupalGet('translate/items/' . $second_task_item->id());
    $title = $this->xpath('//*[@id="edit-dummydeep-nesting-translation-format-guidelines"]/div/h4')[0];
    $this->assertEqual($title->getText(), t('Basic HTML'));

    // Assert the order of the displayed elements.
    $translate_elements = $this->xpath('//*[@id="edit-translation"]/table');

    $ids = [];
    foreach ($translate_elements as $translate_element) {
      $ids[] = (string) $translate_element->getAttribute('id');
    }

    $this->assertEqual($ids[0], 'tmgmt-local-element-dummy-deep-nesting');
    $this->assertEqual($ids[1], 'tmgmt-local-element-second');
    $this->assertEqual($ids[2], 'tmgmt-local-element-third');

    $edit = array(
      'dummy|deep_nesting[translation][value]' => $translation2 = 'German translation of source 2',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('The translation for ' . $second_task_item->label() . ' has been saved.');

    drupal_static_reset('tmgmt_local_task_statistics_load');
    /** @var \Drupal\tmgmt_local\Entity\LocalTask $task */
    $task = \Drupal::entityTypeManager()->getStorage('tmgmt_local_task')->loadUnchanged($task->id());
    $this->assertTrue($task->isPending());

    // Mark the data item as translated but don't save the task item as
    // completed.
    $this->clickLink(t('Translate'));
    $page = $this->getSession()->getPage();
    $page->pressButton('finish-dummy|deep_nesting');
    $this->assertRaw('name="reject-dummy|deep_nesting"', "'✗' button appears.");
    $this->drupalGet('translate/' . $task->id());

    \Drupal::entityTypeManager()->getStorage('tmgmt_local_task_item')->resetCache();
    drupal_static_reset('tmgmt_local_task_statistics_load');
    /** @var \Drupal\tmgmt_local\Entity\LocalTask $task */
    $task = \Drupal::entityTypeManager()->getStorage('tmgmt_local_task')->loadUnchanged($task->id());
    $this->assertTrue($task->isPending());
    list($first_task_item, $second_task_item) = array_values($task->getItems());
    $this->assertTrue($first_task_item->isClosed());
    $this->assertTrue($second_task_item->isPending());

    // Check the job data.
    \Drupal::entityTypeManager()->getStorage('tmgmt_job_item')->resetCache();
    /** @var \Drupal\tmgmt\JobInterface $job */
    $job = Job::load($job->id());
    /** @var \Drupal\tmgmt\JobItemInterface $item2 */
    list($item1, $item2) = array_values($job->getItems());
    $this->assertTrue($item1->isAccepted(), 'Job item 1 is accepted.');
    // The first item should be active.
    $this->assertTrue($item2->isActive(), 'Job item 2 is still active.');

    // Check the overview page, the task should still show in progress.
    $this->drupalGet('translate');
    $this->assertText(t('Pending'));

    // Mark the second item as completed now.
    $this->clickLink(t('View'));
    $this->clickLink(t('Translate'));
    $remaining_translations = [
      'second[translation]' => 'Third translation',
      'third[translation]' => 'Third translation',
    ];
    $this->drupalPostForm(NULL, $remaining_translations, t('Save as completed'));
    $this->assertText('The translation for ' . $second_task_item->label() . ' has been saved as completed.');
    $this->clickLink('View');

    // Review and accept the second item.
    \Drupal::entityTypeManager()->getStorage('tmgmt_job_item')->resetCache();
    drupal_static_reset('tmgmt_local_task_statistics_load');
    $item1 = JobItem::load(2);
    $item1->acceptTranslation();

    // Refresh the page.
    $this->drupalGet('translate');
    // We should have been redirect back to the overview, the task should be
    // completed now.
    $this->assertNoText($task->getJob()->label());
    $this->clickLink(t('Closed'));
    $this->assertText($task->getJob()->label());
    $this->assertText(t('Completed'));

    \Drupal::entityTypeManager()->getStorage('tmgmt_local_task_item')->resetCache();
    $task = LocalTask::load($task->id());
    $this->assertTrue($task->isClosed());
    list($first_task_item, $second_task_item) = array_values($task->getItems());
    $this->assertTrue($first_task_item->isClosed());
    $this->assertTrue($second_task_item->isClosed());

    \Drupal::entityTypeManager()->getStorage('tmgmt_job_item')->resetCache();
    $job = Job::load($job->id());
    list($item1, $item2) = array_values($job->getItems());
    // Job was accepted and finished automatically due to the default approve
    // setting.
    $this->assertTrue($job->isFinished());
    $this->assertEqual($item1->getData(array(
      'dummy',
      'deep_nesting',
      '#translation',
      '#text',
    )), $translation1);
    $this->assertEqual($item2->getData(array(
      'dummy',
      'deep_nesting',
      '#translation',
      '#text',
    )), $translation2);

    // Delete the job, make sure that the corresponding task and task items were
    // deleted.
    $job->delete();
    $this->assertFalse(LocalTask::load($task->id()));
    $this->assertFalse($task->getItems());
  }

  /**
   * Test the allow all setting.
   */
  public function testAllowAll() {
    /** @var Translator $translator */
    $translator = Translator::load('local');

    // Create a job and request a local translation.
    $this->loginAsTranslator();
    $job = $this->createJob();
    $job->translator = $translator->id();
    $job->addItem('test_source', 'test', '1');
    $job->addItem('test_source', 'test', '2');

    $this->assertFalse($job->requestTranslation(), 'Translation request was denied.');

    // Now enable the setting.
    $this->config('tmgmt_local.settings')->set('allow_all', TRUE)->save();
    /** @var Job $job */
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
    $job->translator = $translator->id();

    $this->assertIdentical(NULL, $job->requestTranslation(), 'Translation request was successfull');
    $this->assertTrue($job->isActive());
  }

  public function testAbilitiesAPI() {

    $this->addLanguage('fr');
    $this->addLanguage('ru');
    $this->addLanguage('it');

    $all_assignees = array();

    $assignee1 = $this->drupalCreateUser($this->localTranslatorPermissions);
    $all_assignees[$assignee1->id()] = $assignee1->getDisplayName();
    $this->drupalLogin($assignee1);
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $assignee1->id() . '/edit', $edit, t('Save'));

    $assignee2 = $this->drupalCreateUser($this->localTranslatorPermissions);
    $all_assignees[$assignee2->id()] = $assignee2->getDisplayName();
    $this->drupalLogin($assignee2);
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'ru',
    );
    $this->drupalPostForm('user/' . $assignee2->id() . '/edit', $edit, t('Save'));
    $edit = array(
      'tmgmt_translation_skills[1][language_from]' => 'en',
      'tmgmt_translation_skills[1][language_to]' => 'fr',
    );
    $this->drupalPostForm('user/' . $assignee2->id() . '/edit', $edit, t('Save'));
    $edit = array(
      'tmgmt_translation_skills[2][language_from]' => 'fr',
      'tmgmt_translation_skills[2][language_to]' => 'it',
    );
    $this->drupalPostForm('user/' . $assignee2->id() . '/edit', $edit, t('Save'));

    $assignee3 = $this->drupalCreateUser($this->localTranslatorPermissions);
    $all_assignees[$assignee3->id()] = $assignee3->getDisplayName();
    $this->drupalLogin($assignee3);
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'fr',
      'tmgmt_translation_skills[0][language_to]' => 'ru',
    );
    $this->drupalPostForm('user/' . $assignee3->id() . '/edit', $edit, t('Save'));
    $edit = array(
      'tmgmt_translation_skills[1][language_from]' => 'it',
      'tmgmt_translation_skills[1][language_to]' => 'en',
    );
    $this->drupalPostForm('user/' . $assignee3->id() . '/edit', $edit, t('Save'));

    // Test target languages.
    $target_languages = tmgmt_local_supported_target_languages('fr');
    $this->assertTrue(isset($target_languages['it']));
    $this->assertTrue(isset($target_languages['ru']));
    $target_languages = tmgmt_local_supported_target_languages('en');
    $this->assertTrue(isset($target_languages['fr']));
    $this->assertTrue(isset($target_languages['ru']));

    // Test language pairs.
    $this->assertEqual(tmgmt_local_supported_language_pairs(), array (
      'en__de' =>
        array(
          'source_language' => 'en',
          'target_language' => 'de',
        ),
      'en__ru' =>
        array(
          'source_language' => 'en',
          'target_language' => 'ru',
        ),
      'en__fr' =>
        array(
          'source_language' => 'en',
          'target_language' => 'fr',
        ),
      'fr__it' =>
        array(
          'source_language' => 'fr',
          'target_language' => 'it',
        ),
      'fr__ru' =>
        array(
          'source_language' => 'fr',
          'target_language' => 'ru',
        ),
      'it__en' =>
        array(
          'source_language' => 'it',
          'target_language' => 'en',
        ),
    ));
    $this->assertEqual(tmgmt_local_supported_language_pairs('fr', array($assignee2->id())), array(
      'fr__it' =>
        array(
          'source_language' => 'fr',
          'target_language' => 'it',
        ),
    ));

    // Test if we got all translators.
    $assignees = tmgmt_local_assignees();
    foreach ($all_assignees as $uid => $name) {
      if (!isset($assignees[$uid])) {
        $this->fail('Expected translator not present');
      }
      if (!in_array($name, $all_assignees)) {
        $this->fail('Expected translator name not present');
      }
    }

    // Only translator2 has such abilities.
    $assignees = tmgmt_local_assignees('en', array('ru', 'fr'));
    $this->assertTrue(isset($assignees[$assignee2->id()]));
  }

  /**
   * Test permissions for the tmgmt_local VBO actions.
   */
  public function testVBOPermissions() {
    $translator = Translator::load('local');
    $job = $this->createJob();
    $job->translator = $translator->id();
    $job->settings->job_comment = $job_comment = 'Dummy job comment';
    $job->addItem('test_source', 'test', '1');
    $job->addItem('test_source', 'test', '2');

    // Create another local translator with the required abilities.
    $assignee = $this->loginAsTranslator($this->localTranslatorPermissions);
    // Configure language abilities.
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $assignee->id() . '/edit', $edit, t('Save'));

    $job->requestTranslation();

    $this->drupalGet('manage-translate');
    $this->assertResponse(403);
    $this->drupalGet('translate');
    $edit = array(
      'tmgmt_local_task_bulk_form[0]' => TRUE,
      'action' => 'tmgmt_local_task_assign_to_me',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText(t('Assign to me was applied to 1 item.'));
    $edit = array(
      'tmgmt_local_task_bulk_form[0]' => TRUE,
      'action' => 'tmgmt_local_task_unassign_multiple',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText(t('Unassign was applied to 1 item.'));

    // Login as admin and check VBO submit actions are present.
    $this->loginAsAdmin($this->localManagerPermissions);
    $this->drupalGet('manage-translate');
    $edit = array(
      'tmgmt_local_task_bulk_form[0]' => TRUE,
      'action' => 'tmgmt_local_task_assign_multiple',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $edit = array(
      'tuid' => $assignee->id(),
    );
    $this->drupalPostForm(NULL, $edit, t('Assign tasks'));
    $this->assertText(t('Assigned 1 to user @assignee.', ['@assignee' => $assignee->getAccountName()]));
  }

  /**
   * Tests of the task progress.
   */
  public function testLocalProgress() {
    // Load the local translator.
    $translator = Translator::load('local');
    // Create assignee with the skills.
    $assignee1 = $this->drupalCreateUser($this->localManagerPermissions);
    $this->drupalLogin($assignee1);
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $assignee1->id() . '/edit', $edit, t('Save'));

    // Login as translator.
    $this->loginAsTranslator();
    // Create the basic html format.
    $basic_html_format = FilterFormat::create(array(
      'format' => 'basic_html',
      'name' => 'Basic HTML',
    ));
    $basic_html_format->save();
    // Create a Job.
    $job = $this->createJob();
    $job->translator = $translator->id();
    $item1 = $job->addItem('test_source', 'test', '1');
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'title' => array(
        'deep_nesting' => array(
          '#text' => 'Example text',
          '#label' => 'Label for job item with type test and id 2.',
          '#translate' => TRUE,
        ),
      ),
      'text' => array(
        'deep_nesting' => array(
          '#text' => 'Example text',
          '#label' => 'Label for job item with type test and id 2.',
          '#translate' => TRUE,
        ),
      ),
    ));
    $job->addItem('test_source', 'test', '2');
    $job->save();
    $this->drupalGet($job->toUrl());
    $this->drupalPostForm(NULL, NULL, t('Submit to provider'));

    // Login as assignee.
    $this->drupalLogin($assignee1);

    // Check the task unassigned icon.
    $this->drupalGet('/manage-translate');
    $this->assertTaskStatusIcon(1, 'manage-translate-task', 'unassigned', 'Unassigned');
    // Assign the task and check its icon.
    $edit = array(
      'action' => 'tmgmt_local_task_assign_to_me',
      'tmgmt_local_task_bulk_form[0]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertTaskStatusIcon(1, 'manage-translate-task', 'assigned', 'Needs action');
    // Unassign it back.
    $edit = array(
      'action' => 'tmgmt_local_task_unassign_multiple',
      'tmgmt_local_task_bulk_form[0]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    // Check its unassigned icon.
    $this->drupalGet('/manage-translate');
    $this->assertTaskStatusIcon(1, 'manage-translate-task', 'unassigned', 'Unassigned');

    $this->drupalGet('/translate');

    // Check the unassigned status.
    $this->assertTaskStatusIcon(1, 'task-overview', 'unassigned', 'Unassigned');
    $edit = array(
      'tmgmt_local_task_bulk_form[0]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    // Check the needs action status.
    $this->assertTaskStatusIcon(1, 'task-overview', 'my-tasks', 'Needs action');

    // Check if the task is displayed on pending overview.
    $this->drupalGet('/translate/pending');
    $this->assertTaskStatusIcon(1, 'task-overview', 'pending', 'Needs action');
    $this->drupalGet('/manage-translate/pending');
    $this->assertTaskStatusIcon(1, 'manage-translate-task', 'pending', 'Needs action');
    // Check the icons of the task items.
    $this->drupalGet('/translate/1');
    $this->assertTaskItemStatusIcon('test_source:test:1', 'Untranslated');
    $this->assertTaskItemStatusIcon('test_source:test:2', 'Untranslated');
    $this->assertTaskItemProgress('test_source:test:1', 1, 0, 0);
    $this->assertTaskItemProgress('test_source:test:2', 2, 0, 0);

    // Check the progress bar and status of the task.
    $this->drupalGet('/translate');
    $this->assertTaskProgress(1, 'my-tasks', 3, 0, 0);
    $this->assertTaskStatusIcon(1, 'task-overview', 'my-tasks', 'Needs action');
    $this->drupalGet('/manage-translate');
    $this->assertTaskStatusIcon(1, 'manage-translate-task', 'assigned', 'Needs action');

    // Set two items as translated.
    $page = $this->getSession()->getPage();
    $this->drupalGet('translate/items/1');
    $page->pressButton('finish-dummy|deep_nesting');
    $this->drupalGet('translate/items/2');
    $page->pressButton('finish-title|deep_nesting');
    // Check the task items icons and progress.
    $this->drupalGet('/translate/1');
    $this->assertTaskItemStatusIcon('test_source:test:1', 'Untranslated');
    $this->assertTaskItemStatusIcon('test_source:test:2', 'Untranslated');
    $this->assertTaskItemProgress('test_source:test:1', 0, 1, 0);
    $this->assertTaskItemProgress('test_source:test:2', 1, 1, 0);

    // Check the progress bar and status of the task.
    $this->drupalGet('/translate');
    $this->assertTaskProgress(1, 'my-tasks', 1, 2, 0);
    $this->assertTaskStatusIcon(1, 'task-overview', 'my-tasks', 'Needs action');
    $this->drupalGet('/manage-translate');
    $this->assertTaskStatusIcon(1, 'manage-translate-task', 'assigned', 'Needs action');

    // Save the first item as completed and check item icons and progress.
    $edit = [
      'dummy|deep_nesting[translation]' => 'German translation',
    ];
    $this->drupalPostForm('/translate/items/1', $edit, t('Save as completed'));
    $this->assertTaskItemStatusIcon('test_source:test:1', 'Translated');
    $this->assertTaskItemStatusIcon('test_source:test:2', 'Untranslated');
    $this->assertTaskItemProgress('test_source:test:1', 0, 0, 1);
    $this->assertTaskItemProgress('test_source:test:2', 1, 1, 0);
    // Check the progress bar and status of the task.
    $this->drupalGet('/translate');
    $this->assertTaskProgress(1, 'my-tasks', 1, 1, 1);
    $this->assertTaskStatusIcon(1, 'task-overview', 'my-tasks', 'Needs action');
    $this->drupalGet('/manage-translate');
    $this->assertTaskStatusIcon(1, 'manage-translate-task', 'assigned', 'Needs action');

    // Save the second item as completed.
    $edit = [
      'title|deep_nesting[translation]' => 'German translation of title',
      'text|deep_nesting[translation]' => 'German translation of text',
    ];
    $this->drupalPostForm('/translate/items/2', $edit, t('Save as completed'));
    // Check the icon a progress bar of the task.
    $this->assertTaskProgress(1, 'my-tasks', 0, 0, 3);
    $this->assertTaskStatusIcon(1, 'task-overview', 'my-tasks', 'In review');
    // Check the task items icons.
    $this->drupalGet('/translate/1');
    $this->assertTaskItemStatusIcon('test_source:test:1', 'Translated');
    $this->assertTaskItemStatusIcon('test_source:test:2', 'Translated');
    $this->assertTaskItemProgress('test_source:test:1', 0, 0, 1);
    $this->assertTaskItemProgress('test_source:test:2', 0, 0, 2);

    // Check if the task is displayed on the completed overview.
    $this->drupalGet('/translate/completed');
    $this->assertTaskStatusIcon(1, 'task-overview', 'completed', 'In review');
    $this->drupalGet('/manage-translate/completed');
    $this->assertTaskStatusIcon(1, 'manage-translate-task', 'completed', 'In review');
    // Accept translation of the job items.
    /** @var \Drupal\tmgmt\Entity\Job $job1 */
    $job1 = Job::load($job->id());
    /** @var \Drupal\tmgmt\Entity\JobItem $item */
    foreach ($job1->getItems() as $item) {
      $item->acceptTranslation();
    }
    // Check if the task is displayed on the closed overview.
    $this->drupalGet('/translate/closed');
    $this->assertTaskStatusIcon(1, 'task-overview', 'closed', 'Closed');
    $this->drupalGet('/manage-translate/closed');
    $this->assertTaskStatusIcon(1, 'manage-translate-task', 'closed', 'Closed');

    // Assert the legend.
    $this->drupalGet('/translate/items/' . $item1->id());
    $this->assertRaw('class="tmgmt-color-legend');
  }

  /**
   * Test permissions for the tmgmt_local VBO actions.
   */
  public function testUserPermissionsAccess() {
    $permissions = [
      'administer tmgmt',
      'create translation jobs',
      'accept translation jobs',
      'administer translation tasks',
    ];
    foreach ($permissions as $permission) {
      $user = $this->drupalCreateUser([$permission]);
      $this->drupalLogin($user);
      $this->drupalGet('admin/tmgmt');
      $this->assertText('Translation');
    }
    $user = $this->drupalCreateUser(['provide translation services']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/tmgmt');
    $this->assertText('Local Tasks');
  }

  /**
   * Test the settings of TMGMT local.
   */
  public function testSettings() {
    \Drupal::getContainer()->get('theme_handler')->install(array('seven'));
    $this->drupalPlaceBlock('system_menu_block:account');
    $this->loginAsAdmin($this->localManagerPermissions);
    $edit = [
      'admin_theme' => 'seven',
      'use_admin_theme' => TRUE,
    ];
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));

    $settings = \Drupal::config('tmgmt_local.settings');
    $this->assertTrue($settings->get('use_admin_theme'));
    $this->drupalGet('admin/tmgmt');
    $this->assertText('Translate');
    $this->drupalGet('<front>');
    $this->assertNoText('Translate');

    $this->drupalGet('admin/tmgmt/settings');
    $edit = [
      'use_admin_theme' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $settings = \Drupal::config('tmgmt_local.settings');
    $this->assertFalse($settings->get('use_admin_theme'));
    $this->drupalGet('admin/tmgmt');
    $this->assertNoText('Translate');
    $this->drupalGet('<front>');
    $this->assertText('Translate');
  }

  /**
   * Test the task and task items are closed and completed when aborting a Job.
   */
  public function testAbort() {
    // Prepare the scenario.
    $translator = Translator::load('local');
    $this->loginAsTranslator($this->localTranslatorPermissions);
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $this->translator_user->id() . '/edit', $edit, t('Save'));
    $job = $this->createJob();
    $job->translator = $translator->id();
    $first_job_item = $job->addItem('test_source', 'test', '1');
    \Drupal::state()->set('tmgmt.test_source_data', [
      'dummy' => [
        'deep_nesting' => [
          '#text' => file_get_contents(drupal_get_path('module', 'tmgmt') . '/tests/testing_html/sample.html'),
          '#label' => 'Label for job item with type test and id 2.',
          '#translate' => TRUE,
          '#format' => 'basic_html',
        ],
      ],
    ]);
    $second_job_item = $job->addItem('test_source', 'test', '2');
    $job->save();
    $edit = [
      'settings[translator]' => $this->translator_user->id(),
    ];
    $this->drupalPostForm($job->toUrl(), $edit, t('Submit to provider'));

    // Check Job Item abort, close the task item.
    $this->drupalGet('admin/tmgmt/items/' . $first_job_item->id() . '/abort');
    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->drupalGet('translate');
    $this->assertNoRaw('views-field-progress">Closed');
    $this->clickLink(t('View'));
    $this->assertRaw('views-field-progress">Closed');
    $this->assertTrue(preg_match('|translate/(\d+)|', $this->getUrl(), $matches), 'Task found');
    /** @var \Drupal\tmgmt_local\Entity\LocalTask $task */
    $task = \Drupal::entityTypeManager()->getStorage('tmgmt_local_task')->load($matches[1]);
    $this->assertTrue($task->isPending());

    // Checking if the 'Save as completed' button is not displayed.
    $this->drupalGet('translate/items/1');
    $elements = $this->xpath('//*[@id="edit-save-as-completed"]');
    $this->assertTrue(empty($elements), "'Save as completed' button does not appear.");
    // Checking if the 'Save' button is not displayed.
    $elements = $this->xpath('//*[@id="edit-save"]');
    $this->assertTrue(empty($elements), "'Save' button does not appear.");
    // Checking if the 'Preview' button is not displayed.
    $elements = $this->xpath('//*[@id="edit-preview"]');
    $this->assertTrue(empty($elements), "'Preview' button does not appear.");
    // Checking if the '✓' button is not displayed.
    $elements = $this->xpath('//*[@id="edit-dummydeep-nesting-actions-finish-dummydeep-nesting"]');
    $this->assertTrue(empty($elements), "'✓' button does not appear.");
    // Checking translation is readonly.
    $this->assertRaw('data-drupal-selector="edit-dummydeep-nesting-translation" readonly="readonly"');

    // Check closing all task items also closes the task.
    $this->drupalGet('admin/tmgmt/items/' . $second_job_item->id() . '/abort');
    $this->drupalPostForm(NULL, [], t('Confirm'));
    $this->drupalGet('translate/closed');
    $this->assertText($task->label());
    $task = \Drupal::entityTypeManager()->getStorage('tmgmt_local_task')->loadUnchanged($matches[1]);
    $this->assertTrue($task->isClosed());
    $this->assertRaw('views-field-progress">Closed');
  }

}
