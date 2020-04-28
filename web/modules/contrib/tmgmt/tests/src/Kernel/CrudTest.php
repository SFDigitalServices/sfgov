<?php

namespace Drupal\Tests\tmgmt\Kernel;

use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\Entity\Translator;

/**
 * Basic crud operations for jobs and translators
 *
 * @group tmgmt
 */
class CrudTest extends TMGMTKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    \Drupal::service('router.builder')->rebuild();
    $this->installEntitySchema('tmgmt_remote');
  }

  /**
   * Test crud operations of translators.
   */
  function testTranslators() {
    $translator = $this->createTranslator();

    $loaded_translator = Translator::load($translator->id());
    $this->assertEqual($translator->id(), $loaded_translator->id());
    $this->assertEqual($translator->label(), $loaded_translator->label());
    $this->assertEqual($translator->getSettings(), $loaded_translator->getSettings());

    // Update the settings.
    $translator->setSetting('new_key', $this->randomString());
    $translator->save();

    $loaded_translator = Translator::load($translator->id());
    $this->assertEqual($translator->id(), $loaded_translator->id());
    $this->assertEqual($translator->label(), $loaded_translator->label());
    $this->assertEqual($translator->getSettings(), $loaded_translator->getSettings());

    // Delete the translator, make sure the translator is gone.
    $translator->delete();
    $this->assertNull(Translator::load($translator->id()));
  }

  /**
   * Tests job item states for 'reject' / 'submit' settings action job states.
   */
  public function testRejectedJob() {
    $job = $this->createJob();

    // Change job state to 'reject' through the API and request a translation.
    $job->translator = $this->default_translator->id();
    $job->settings->action = 'reject';
    $job->save();
    $job_item = $job->addItem('test_source', 'type', 1);
    $job->requestTranslation();

    // Check that job is rejected and job item is NOT active.
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadUnchanged($job->id());
    $this->assertTrue($job->isRejected());
    $job_item = \Drupal::entityTypeManager()->getStorage('tmgmt_job_item')->loadUnchanged($job_item->id());
    $this->assertTrue($job_item->isInactive());

    // Change job state to 'submit' through the API and request a translation.
    $job->settings->action = 'submit';
    $job->save();
    $job->requestTranslation();

    // Check that job is active and job item IS active.
    $this->assertTrue($job->isActive());
    $this->assertTrue($job_item->isActive());
  }

  /**
   * Test crud operations of jobs.
   */
  function testJobs() {
    $job = $this->createJob();

    $this->assertEqual(Job::TYPE_NORMAL, $job->getJobType());

    $loaded_job = Job::load($job->id());

    $this->assertEqual($job->getSourceLangcode(), $loaded_job->getSourceLangcode());
    $this->assertEqual($job->getTargetLangcode(), $loaded_job->getTargetLangcode());

    // Assert that the created and changed information has been set to the
    // default value.
    $this->assertTrue($loaded_job->getCreatedTime() > 0);
    $this->assertTrue($loaded_job->getChangedTime() > 0);
    $this->assertEqual(0, $loaded_job->getState());

    // Update the settings.
    $job->reference = 7;
    $this->assertEqual(SAVED_UPDATED, $job->save());

    $loaded_job = Job::load($job->id());

    $this->assertEqual($job->getReference(), $loaded_job->getReference());

    // Test the job items.
    $item1 = $job->addItem('test_source', 'type', 5);
    $item2 = $job->addItem('test_source', 'type', 4);

    // Load and compare the items.
    $items = $job->getItems();
    $this->assertEqual(2, count($items));

    $this->assertEqual($item1->getPlugin(), $items[$item1->id()]->getPlugin());
    $this->assertEqual($item1->getItemType(), $items[$item1->id()]->getItemType());
    $this->assertEqual($item1->getItemId(), $items[$item1->id()]->getItemId());
    $this->assertEqual($item2->getPlugin(), $items[$item2->id()]->getPlugin());
    $this->assertEqual($item2->getItemType(), $items[$item2->id()]->getItemType());
    $this->assertEqual($item2->getItemId(), $items[$item2->id()]->getItemId());

    // Delete the job and make sure it is gone.
    $job->delete();
    $this->assertFalse(Job::load($job->id()));
  }

  function testRemoteMappings() {

    $data_key = '5][test_source][type';

    $translator = $this->createTranslator();
    $job = $this->createJob();
    $job->translator = $translator->id();
    $job->save();
    $item1 = $job->addItem('test_source', 'type', 5);
    $item2 = $job->addItem('test_source', 'type', 4);

    $mapping_data = array(
      'remote_identifier_2' => 'id12',
      'remote_identifier_3' => 'id13',
      'amount' => 1043,
      'currency' => 'EUR',
    );

    $result = $item1->addRemoteMapping($data_key, 'id11', $mapping_data);
    $this->assertEqual($result, SAVED_NEW);

    $job_mappings = $job->getRemoteMappings();
    $item_mappings = $item1->getRemoteMappings();

    $job_mapping = array_shift($job_mappings);
    $item_mapping = array_shift($item_mappings);

    $_job = $job_mapping->getJob();
    $this->assertEqual($job->id(), $_job->id());

    $_job = $item_mapping->getJob();
    $this->assertEqual($job->id(), $_job->id());

    $_item1 = $item_mapping->getJobItem();
    $this->assertEqual($item1->id(), $_item1->id());

    $remote_mappings = RemoteMapping::loadByRemoteIdentifier('id11', 'id12', 'id13');
    $remote_mapping = array_shift($remote_mappings);
    $this->assertEqual($remote_mapping->id(), $item1->id());
    $this->assertEqual($remote_mapping->getAmount(), $mapping_data['amount']);
    $this->assertEqual($remote_mapping->getCurrency(), $mapping_data['currency']);

    $this->assertEqual(count(RemoteMapping::loadByRemoteIdentifier('id11')), 1);
    $this->assertEqual(count(RemoteMapping::loadByRemoteIdentifier('id11', '')), 0);
    $this->assertEqual(count(RemoteMapping::loadByRemoteIdentifier('id11', NULL, '')), 0);
    $this->assertEqual(count(RemoteMapping::loadByRemoteIdentifier(NULL, NULL, 'id13')), 1);

    $this->assertEqual($remote_mapping->getRemoteIdentifier1(), 'id11');
    $this->assertEqual($remote_mapping->getRemoteIdentifier2(), 'id12');
    $this->assertEqual($remote_mapping->getRemoteIdentifier3(), 'id13');

    // Test remote data.
    $item_mapping->addRemoteData('test_data', 'test_value');
    $item_mapping->save();
    $item_mapping = RemoteMapping::load($item_mapping->id());
    $this->assertEqual($item_mapping->getRemoteData('test_data'), 'test_value');

    // Add mapping to the other job item as well.
    $item2->addRemoteMapping($data_key, 'id21', array('remote_identifier_2' => 'id22', 'remote_identifier_3' => 'id23'));

    // Test deleting.

    // Delete item1.
    $item1->delete();
    // Test if mapping for item1 has been removed as well.

    $this->assertEqual(count(RemoteMapping::loadByLocalData(NULL, $item1->id())), 0);

    // We still should have mapping for item2.
    $this->assertEqual(count(RemoteMapping::loadByLocalData(NULL, $item2->id())), 1);

    // Now delete the job and see if remaining mappings were removed as well.
    $job->delete();
    $this->assertEqual(count(RemoteMapping::loadByLocalData(NULL, $item2->id())), 0);
  }

  /**
   * Test crud operations of job items.
   */
  function testJobItems() {
    $job = $this->createJob();

    // Add some test items.
    $item1 = $job->addItem('test_source', 'type', 5);
    $item2 = $job->addItem('test_source', 'test_with_long_label', 4);

    // Test single load callback.
    $item = JobItem::load($item1->id());
    $this->assertEqual($item1->getPlugin(), $item->getPlugin());
    $this->assertEqual($item1->getItemType(), $item->getItemType());
    $this->assertEqual($item1->getItemId(), $item->getItemId());

    // Test multiple load callback.
    $items = JobItem::loadMultiple(array($item1->id(), $item2->id()));

    $this->assertEqual(2, count($items));

    $this->assertEqual($item1->getPlugin(), $items[$item1->id()]->getPlugin());
    $this->assertEqual($item1->getItemType(), $items[$item1->id()]->getItemType());
    $this->assertEqual($item1->getItemId(), $items[$item1->id()]->getItemId());
    $this->assertEqual($item2->getPlugin(), $items[$item2->id()]->getPlugin());
    $this->assertEqual($item2->getItemType(), $items[$item2->id()]->getItemType());
    $this->assertEqual($item2->getItemId(), $items[$item2->id()]->getItemId());
    // Test the second item label length - it must not exceed the
    // TMGMT_JOB_LABEL_MAX_LENGTH.
    $this->assertTrue(Job::LABEL_MAX_LENGTH >= strlen($items[$item2->id()]->label()));
  }

  /**
   * Tests adding translated data and revision handling.
   */
  function testAddingTranslatedData() {
    $translator = $this->createTranslator();
    $job = $this->createJob();
    $job->translator = $translator->id();
    $job->save();

    // Add some test items.
    $item1 = $job->addItem('test_source', 'test_with_long_label', 5);
    // Test the job label - it must not exceed the TMGMT_JOB_LABEL_MAX_LENGTH.
    $this->assertTrue(Job::LABEL_MAX_LENGTH >= strlen($job->label()));

    $key = array('dummy', 'deep_nesting');

    $translation['dummy']['deep_nesting']['#text'] = 'translated 1';
    $item1->addTranslatedData($translation);
    $data = $item1->getData($key);

    // Check job messages.
    $messages = $job->getMessages();
    $this->assertEqual(count($messages), 1);
    $last_message = end($messages);
    $this->assertEqual($last_message->message->value, 'The translation of <a href=":source_url">@source</a> to @language is finished and can now be <a href=":review_url">reviewed</a>.');

    // Initial state - translation has been received for the first time.
    $this->assertEqual($data['#translation']['#text'], 'translated 1');
    $this->assertTrue(empty($data['#translation']['#text_revisions']));
    $this->assertEqual($data['#translation']['#origin'], 'remote');
    $this->assertEqual($data['#translation']['#timestamp'], REQUEST_TIME);

    // Set status back to pending as if the data item was rejected.
    $item1->updateData(array('dummy', 'deep_nesting'), array('#status' => TMGMT_DATA_ITEM_STATE_PENDING));
    // Add same translation text.
    $translation['dummy']['deep_nesting']['#text'] = 'translated 1';
    $item1->addTranslatedData($translation);
    $data = $item1->getData($key);
    // Check if the status has been updated back to translated.
    $this->assertEqual($data['#status'], TMGMT_DATA_ITEM_STATE_TRANSLATED);

    // Add translation, however locally customized.
    $translation['dummy']['deep_nesting']['#text'] = 'translated 2';
    $translation['dummy']['deep_nesting']['#origin'] = 'local';
    $translation['dummy']['deep_nesting']['#timestamp'] = REQUEST_TIME - 5;
    $item1->addTranslatedData($translation);
    $data = $item1->getData($key);

    // The translation text is updated.
    $this->assertEqual($data['#translation']['#text'], 'translated 2');
    $this->assertEqual($data['#translation']['#timestamp'], REQUEST_TIME - 5);

    // Previous translation is among text_revisions.
    $this->assertEqual($data['#translation']['#text_revisions'][0]['#text'], 'translated 1');
    $this->assertEqual($data['#translation']['#text_revisions'][0]['#origin'], 'remote');
    $this->assertEqual($data['#translation']['#text_revisions'][0]['#timestamp'], REQUEST_TIME);
    // Current translation origin is local.
    $this->assertEqual($data['#translation']['#origin'], 'local');

    // Check job messages.
    $messages = $job->getMessages();
    $this->assertEqual(count($messages), 1);

    // Add translation - not local.
    $translation['dummy']['deep_nesting']['#text'] = 'translated 3';
    unset($translation['dummy']['deep_nesting']['#origin']);
    unset($translation['dummy']['deep_nesting']['#timestamp']);
    $item1->addTranslatedData($translation);
    $data = $item1->getData($key);

    // The translation text is NOT updated.
    $this->assertEqual($data['#translation']['#text'], 'translated 2');
    $this->assertEqual($data['#translation']['#timestamp'], REQUEST_TIME - 5);
    // Received translation is the latest revision.
    $last_revision = end($data['#translation']['#text_revisions']);
    $this->assertEqual($last_revision['#text'], 'translated 3');
    $this->assertEqual($last_revision['#origin'], 'remote');
    $this->assertEqual($last_revision['#timestamp'], REQUEST_TIME);
    // Current translation origin is local.
    $this->assertEqual($data['#translation']['#origin'], 'local');

    // Check job messages.
    $messages = $job->getMessages();
    $this->assertEqual(count($messages), 2);
    $last_message = end($messages);
    $this->assertEqual($last_message->message->value, 'Translation for customized @key received. Revert your changes if you wish to use it.');

    // Revert to previous revision which is the latest received translation.
    $item1->dataItemRevert($key);
    $data = $item1->getData($key);

    // The translation text is updated.
    $this->assertEqual($data['#translation']['#text'], 'translated 3');
    $this->assertEqual($data['#translation']['#origin'], 'remote');
    $this->assertEqual($data['#translation']['#timestamp'], REQUEST_TIME);
    // Latest revision is now the formerly added local translation.
    $last_revision = end($data['#translation']['#text_revisions']);
    $this->assertTrue($last_revision['#text'], 'translated 2');
    $this->assertTrue($last_revision['#origin'], 'remote');
    $this->assertEqual($last_revision['#timestamp'], REQUEST_TIME - 5);

    // Check job messages.
    $messages = $job->getMessages();
    $this->assertEqual(count($messages), 3);
    $last_message = end($messages);
    $this->assertEqual($last_message->message->value, 'Translation for @key reverted to the latest version.');

    // There should be three revisions now.
    $this->assertEqual(count($data['#translation']['#text_revisions']), 3);

    // Attempt to update the translation with the same text, this should not
    // lead to a new revision.
    $translation['dummy']['deep_nesting']['#text'] = 'translated 3';
    //unset($translation['dummy']['deep_nesting']['#origin']);
    //unset($translation['dummy']['deep_nesting']['#timestamp']);
    $item1->addTranslatedData($translation);
    $data = $item1->getData($key);
    $this->assertEqual(count($data['#translation']['#text_revisions']), 3);

    // Mark the translation as reviewed, a new translation should not update the
    // existing one but create a new translation.
    $item1->updateData($key, array('#status' => TMGMT_DATA_ITEM_STATE_REVIEWED));
    $translation['dummy']['deep_nesting']['#text'] = 'translated 4';
    $item1->addTranslatedData($translation);
    $data = $item1->getData($key);

    // The translation text is NOT updated.
    $this->assertEqual($data['#translation']['#text'], 'translated 3');
    // Received translation is the latest revision.
    $this->assertEqual(count($data['#translation']['#text_revisions']), 4);
    $last_revision = end($data['#translation']['#text_revisions']);
    $this->assertEqual($last_revision['#text'], 'translated 4');
    $this->assertEqual($last_revision['#origin'], 'remote');
    $this->assertEqual($last_revision['#timestamp'], REQUEST_TIME);

    // Check job messages.
    $messages = $job->getMessages();
    $this->assertEqual(count($messages), 4);
    $last_message = end($messages);
    $this->assertEqual($last_message->message->value, 'Translation for already reviewed @key received and stored as a new revision. Revert to it if you wish to use it.');
  }

  /**
   * Test the calculations of the counters.
   */
  function testJobItemsCounters() {
    $job = $this->createJob();

    // Some test data items.
    $data1 = array(
      '#text' => 'The text to be translated.',
    );
    $data2 = array(
      '#text' => 'The text to be translated.',
      '#translation' => '',
    );
    $data3 = array(
      '#text' => 'The text to be translated.',
      '#translation' => 'The translated data. Set by the translator plugin.',
    );
    $data4 = array(
      '#text' => 'Another, longer text to be translated.',
      '#translation' => 'The translated data. Set by the translator plugin.',
      '#status' => TMGMT_DATA_ITEM_STATE_REVIEWED,
    );
    $data5 = array(
      '#label' => 'label',
      'data1' => $data1,
      'data4' => $data4,
    );
    $data6 = array(
      '#text' => '<p>Test the HTML tags count.</p>',
    );

    // No data items.
    $this->assertEqual(0, $job->getCountPending());
    $this->assertEqual(0, $job->getCountTranslated());
    $this->assertEqual(0, $job->getCountReviewed());
    $this->assertEqual(0, $job->getCountAccepted());
    $this->assertEqual(0, $job->getWordCount());

    // Add a test items.
    $job_item1 = tmgmt_job_item_create('plugin', 'type', 4, array('tjid' => $job->id()));
    $job_item1->save();

    // No pending, translated and confirmed data items.
    $job = Job::load($job->id());
    $job_item1 = JobItem::load($job_item1->id());
    drupal_static_reset('tmgmt_job_statistics_load');
    $this->assertEqual(0, $job_item1->getCountPending());
    $this->assertEqual(0, $job_item1->getCountTranslated());
    $this->assertEqual(0, $job_item1->getCountReviewed());
    $this->assertEqual(0, $job_item1->getCountAccepted());
    $this->assertEqual(0, $job->getCountPending());
    $this->assertEqual(0, $job->getCountTranslated());
    $this->assertEqual(0, $job->getCountReviewed());
    $this->assertEqual(0, $job->getCountAccepted());

    // Add an untranslated data item.
    $job_item1->updateData('data_item1', $data1);
    $job_item1->save();

    // One pending data items.
    $job = Job::load($job->id());
    $job_item1 = JobItem::load($job_item1->id());
    drupal_static_reset('tmgmt_job_statistics_load');
    $this->assertEqual(1, $job_item1->getCountPending());
    $this->assertEqual(0, $job_item1->getCountTranslated());
    $this->assertEqual(0, $job_item1->getCountReviewed());
    $this->assertEqual(5, $job_item1->getWordCount());
    $this->assertEqual(1, $job->getCountPending());
    $this->assertEqual(0, $job->getCountReviewed());
    $this->assertEqual(0, $job->getCountTranslated());
    $this->assertEqual(5, $job->getWordCount());


    // Add another untranslated data item.
    // Test with an empty translation set.
    $job_item1->updateData('data_item1', $data2, TRUE);
    $job_item1->save();

    // One pending data items.
    $job = Job::load($job->id());
    $job_item1 = JobItem::load($job_item1->id());
    drupal_static_reset('tmgmt_job_statistics_load');
    $this->assertEqual(1, $job_item1->getCountPending());
    $this->assertEqual(0, $job_item1->getCountTranslated());
    $this->assertEqual(0, $job_item1->getCountReviewed());
    $this->assertEqual(5, $job_item1->getWordCount());
    $this->assertEqual(1, $job->getCountPending());
    $this->assertEqual(0, $job->getCountTranslated());
    $this->assertEqual(0, $job->getCountReviewed());
    $this->assertEqual(5, $job->getWordCount());

    // Add a translated data item.
    $job_item1->updateData('data_item1', $data3, TRUE);
    $job_item1->save();

    // One translated data items.
    drupal_static_reset('tmgmt_job_statistics_load');
    $this->assertEqual(0, $job_item1->getCountPending());
    $this->assertEqual(1, $job_item1->getCountTranslated());
    $this->assertEqual(0, $job_item1->getCountReviewed());
    $this->assertEqual(0, $job->getCountPending());
    $this->assertEqual(0, $job->getCountReviewed());
    $this->assertEqual(1, $job->getCountTranslated());

    // Add a confirmed data item.
    $job_item1->updateData('data_item1', $data4, TRUE);
    $job_item1->save();

    // One reviewed data item.
    drupal_static_reset('tmgmt_job_statistics_load');
    $this->assertEqual(1, $job_item1->getCountReviewed());
    $this->assertEqual(1, $job->getCountReviewed());

    // Add a translated and an untranslated and a confirmed data item
    $job = Job::load($job->id());
    $job_item1 = JobItem::load($job_item1->id());
    $job_item1->updateData('data_item1', $data1, TRUE);
    $job_item1->updateData('data_item2', $data3, TRUE);
    $job_item1->updateData('data_item3', $data4, TRUE);
    $job_item1->save();

    // One pending and translated data items each.
    drupal_static_reset('tmgmt_job_statistics_load');
    $this->assertEqual(1, $job->getCountPending());
    $this->assertEqual(1, $job->getCountTranslated());
    $this->assertEqual(1, $job->getCountReviewed());
    $this->assertEqual(16, $job->getWordCount());

    // Add nested data items.
    $job_item1->updateData('data_item1', $data5, TRUE);
    $job_item1->save();

    // One pending data items.
    $job = Job::load($job->id());
    $job_item1 = JobItem::load($job_item1->id());
    $this->assertEqual('label', $job_item1->getData()['data_item1']['#label']);
    $this->assertEqual(3, count($job_item1->getData()['data_item1']));

    // Add a greater number of data items
    for ($index = 1; $index <= 3; $index++) {
      $job_item1->updateData('data_item' . $index, $data1, TRUE);
    }
    for ($index = 4; $index <= 10; $index++) {
      $job_item1->updateData('data_item' . $index, $data3, TRUE);
    }
    for ($index = 11; $index <= 15; $index++) {
      $job_item1->updateData('data_item' . $index, $data4, TRUE);
    }
    $job_item1->save();

    // 3 pending and 7 translated data items each.
    $job = Job::load($job->id());
    drupal_static_reset('tmgmt_job_statistics_load');
    $this->assertEqual(3, $job->getCountPending());
    $this->assertEqual(7, $job->getCountTranslated());
    $this->assertEqual(5, $job->getCountReviewed());

    // Check for HTML tags count.
    $job_item1->updateData('data_item1', $data6);
    $job_item1->save();
    $this->assertEqual(2, $job_item1->getTagsCount());

    // Add several job items
    $job_item2 = tmgmt_job_item_create('plugin', 'type', 5, array('tjid' => $job->id()));
    for ($index = 1; $index <= 4; $index++) {
      $job_item2->updateData('data_item' . $index, $data1, TRUE);
    }
    for ($index = 5; $index <= 12; $index++) {
      $job_item2->updateData('data_item' . $index, $data3, TRUE);
    }
    for ($index = 13; $index <= 16; $index++) {
      $job_item2->updateData('data_item' . $index, $data4, TRUE);
    }
    $job_item2->save();

    // 3 pending and 7 translated data items each.
    $job = Job::load($job->id());
    drupal_static_reset('tmgmt_job_statistics_load');
    $this->assertEqual(7, $job->getCountPending());
    $this->assertEqual(15, $job->getCountTranslated());
    $this->assertEqual(9, $job->getCountReviewed());

    // Accept the job items.
    foreach ($job->getItems() as $item) {
      // Set the state directly to avoid triggering translator and source
      // controllers that do not exist.
      $item->setState(JobItem::STATE_ACCEPTED);
      $item->save();
    }
    drupal_static_reset('tmgmt_job_statistics_load');
    $this->assertEqual(0, $job->getCountPending());
    $this->assertEqual(0, $job->getCountTranslated());
    $this->assertEqual(0, $job->getCountReviewed());
    $this->assertEqual(31, $job->getCountAccepted());
  }

  /**
   * Test crud operations of jobs.
   */
  public function testContinuousTranslators() {
    $translator = $this->createTranslator();
    $this->assertTrue($translator->getPlugin() instanceof ContinuousTranslatorInterface);

    $job = $this->createJob('en', 'de', 0, ['job_type' => Job::TYPE_CONTINUOUS]);

    $this->assertEqual(Job::TYPE_CONTINUOUS, $job->getJobType());
    $job->translator = $translator->id();
    $job->save();

    // Add a test item.
    $item = $job->addItem('test_source', 'test', 1);

    /** @var ContinuousTranslatorInterface $plugin */
    $plugin = $job->getTranslatorPlugin();
    $plugin->requestJobItemsTranslation([$item]);

    $this->assertEqual($item->getData()['dummy']['deep_nesting']['#translation']['#text'], 'de(de-ch): Text for job item with type test and id 1.');
  }

  /**
   * Tests that with the preliminary state the item does not change.
   */
  public function testPreliminaryState() {
    $translator = $this->createTranslator();
    $job = $this->createJob();
    $job->translator = $translator->id();
    $job->save();

    // Add some test items.
    $item = $job->addItem('test_source', 'test', 1);

    $key = array('dummy', 'deep_nesting');

    // Test with preliminary state.
    $translation['dummy']['deep_nesting']['#text'] = 'translated';
    $item->addTranslatedData($translation, [], TMGMT_DATA_ITEM_STATE_PRELIMINARY);
    $this->assertEqual($item->getData($key)['#status'], TMGMT_DATA_ITEM_STATE_PRELIMINARY);
    $this->assertTrue($item->isActive());

    // Test with empty state.
    $item->addTranslatedData($translation);
    $this->assertEqual($item->getData($key)['#status'], TMGMT_DATA_ITEM_STATE_PRELIMINARY);
    $this->assertTrue($item->isActive());

    // Test without state.
    $item->addTranslatedData($translation, [], TMGMT_DATA_ITEM_STATE_TRANSLATED);
    $this->assertEqual($item->getData($key)['#status'], TMGMT_DATA_ITEM_STATE_TRANSLATED);
    $this->assertTrue($item->isNeedsReview());
  }

}
