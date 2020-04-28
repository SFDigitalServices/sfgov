<?php

namespace Drupal\Tests\tmgmt\Kernel;

use Drupal\tmgmt\Entity\Job;

/**
 * Tests interaction between core and the plugins.
 *
 * @group tmgmt
 */
class PluginsTest extends TMGMTKernelTestBase {

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    \Drupal::service('router.builder')->rebuild();
  }


  function createJobWithItems($action = 'translate') {
    $job = parent::createJob();

    for ($i = 1; $i < 3; $i++) {
      if ($i == 3) {
        // Explicitly define the data for the third item.
        $data['data'] = array(
          'dummy' => array(
            'deep_nesting' => array(
              '#text' => 'Stored data',
            ),
          ),
        );
        $job->addItem('test_source', 'test', $i, array($data));
      }
      $job->addItem('test_source', 'test', $i);
    }

    // Manually specify the translator for now.
    $job->translator = $this->default_translator->id();
    $job->settings = array('action' => $action);

    return $job;
  }

  function testBasicWorkflow() {

    // Submit a translation job.
    $submit_job = $this->createJobWithItems('submit');
    $submit_job->requestTranslation();
    $submit_job = Job::load($submit_job->id());
    $this->assertTrue($submit_job->isActive());
    $messages = $submit_job->getMessages();
    $last_message = end($messages);
    $this->assertEqual('Test submit.', $last_message->message->value);

    // Translate a job.
    $translate_job = $this->createJobWithItems('translate');
    $translate_job->requestTranslation();
    $translate_job = Job::load($translate_job->id());
    foreach ($translate_job->getItems() as $job_item) {
      $this->assertTrue($job_item->isNeedsReview());
    }

    $messages = $translate_job->getMessages();
    // array_values() results in numeric keys, which is necessary for list.
    list($debug, $translated, $needs_review) = array_values($messages);
    $this->assertEqual('Test translator called.', $debug->message->value);
    $this->assertEqual('debug', $debug->type->value);
    $this->assertEqual('Test translation created.', $translated->message->value);
    $this->assertEqual('status', $translated->type->value);

    // The third message is specific to a job item and has different state
    // constants.
    $this->assertEqual('The translation of <a href=":source_url">@source</a> to @language is finished and can now be <a href=":review_url">reviewed</a>.', $needs_review->message->value);
    $this->assertEqual('status', $needs_review->type->value);

    $i = 1;
    foreach ($translate_job->getItems() as $item) {
      // Check the translated text.
      if ($i != 3) {
        $expected_text = 'de(de-ch): Text for job item with type ' . $item->getItemType() . ' and id ' . $item->getItemId() . '.';
      }
      else {
        // The third item has an explicitly stored data value.
        $expected_text = 'de(de-ch): Stored data';
      }
      $item_data = $item->getData();
      $this->assertEqual($expected_text, $item_data['dummy']['deep_nesting']['#translation']['#text']);
      $i++;
    }

    foreach ($translate_job->getItems() as $job_item) {
      $job_item->acceptTranslation();
    }

    // @todo Accepting does not result in messages on the job anymore.
    // Update once there are job item messages.
    /*
    $messages = $translate_job->getMessages();
    $last_message = end($messages);
    $this->assertEqual('Job accepted', $last_message->message->value);
    $this->assertEqual('status', $last_message->type);*/

    // Check if the translations have been "saved".
    foreach ($translate_job->getItems() as $item) {
      $this->assertTrue(\Drupal::state()->get('tmgmt_test_saved_translation_' . $item->getItemType() . '_' . $item->getItemId(), FALSE));
    }

    // A rejected job.
    $reject_job = $this->createJobWithItems('reject');
    $reject_job->requestTranslation();
    // Still rejected.
    $this->assertTrue($reject_job->isRejected());

    $messages = $reject_job->getMessages();
    $last_message = end($messages);
    $this->assertEqual('This is not supported.', $last_message->message->value);
    $this->assertEqual('error', $last_message->getType());

    // A failing job.
    $failing_job = $this->createJobWithItems('fail');
    $failing_job->requestTranslation();
    // Still new.
    $this->assertTrue($failing_job->isUnprocessed());

    $messages = $failing_job->getMessages();
    $last_message = end($messages);
    $this->assertEqual('Service not reachable.', $last_message->message->value);
    $this->assertEqual('error', $last_message->getType());
  }

  /**
   * Tests escaping and unescaping text.
   */
  function testEscaping() {
    $plugin = $this->default_translator->getPlugin();

    $tests = array(
      array(
        'item' => array('#text' => 'no escaping'),
        'expected' => 'no escaping',
      ),
      array(
        'item' => array(
          '#text' => 'single placeholder',
          '#escape' => array(
            7 => array('string' => 'placeholder'),
           ),
        ),
        'expected' => 'single [[[placeholder]]]',
      ),
      array(
        'item' => array(
          '#text' => 'two placeholder, the second placeholder',
          '#escape' => array(
            4 => array('string' => 'placeholder'),
            28 => array('string' => 'placeholder'),
          ),
        ),
        'expected' => 'two [[[placeholder]]], the second [[[placeholder]]]',
      ),
      array(
        'item' => array(
          '#text' => 'something, something else',
          '#escape' => array(
            0 => array('string' => 'something'),
            21 => array('string' => 'else'),
          ),
        ),
        'expected' => '[[[something]]], something [[[else]]]',
      ),
      array(
        'item' => array(
          '#text' => 'something, something else',
          '#escape' => array(
            21 => array('string' => 'else'),
            11 => array('string' => 'something'),
          ),
        ),
        'expected' => 'something, [[[something]]] [[[else]]]',
      ),
    );

    foreach ($tests as $test) {
      $escaped = $plugin->escapeText($test['item']);
      // Assert that the string was escaped as expected.
      $this->assertEqual($escaped, $test['expected']);

      // Assert that the string is the same as the original when unescaped.
      $this->assertEqual($plugin->unescapeText($escaped), $test['item']['#text']);
    }
  }

  /**
   * Tests language matching.
   */
  public function testLanguageMatching() {
    // Exact match.
    $language = 'en';
    $remote_languages = ['en-GB-London' => 'English (London - United Kingdom)', 'en' => 'English'];
    $matching_language = \Drupal::service('tmgmt.language_matcher')->getMatchingLangcode($language, $remote_languages);
    $this->assertEquals('en', $matching_language);

    $language = 'en-US';
    $remote_languages = ['en-GB' => 'English (United Kingdom)', 'en-US' => 'English (United States)'];
    $matching_language = \Drupal::service('tmgmt.language_matcher')->getMatchingLangcode($language, $remote_languages);
    $this->assertEquals('en-US', $matching_language);

    $language = 'en-GB-London';
    $remote_languages = ['en-GB' => 'English (United Kingdom)', 'en-GB-London' => 'English (London - United Kingdom)'];
    $matching_language = \Drupal::service('tmgmt.language_matcher')->getMatchingLangcode($language, $remote_languages);
    $this->assertEquals('en-GB-London', $matching_language);

    // Partial match.
    $language = 'en';
    $remote_languages = ['en-GB' => 'English (United Kingdom)', 'en-US' => 'English (United States)'];
    $matching_language = \Drupal::service('tmgmt.language_matcher')->getMatchingLangcode($language, $remote_languages);
    $this->assertEquals('en-GB', $matching_language);

    $language = 'en-GB';
    $remote_languages = ['en' => 'English', 'en-US' => 'English (United States)'];
    $matching_language = \Drupal::service('tmgmt.language_matcher')->getMatchingLangcode($language, $remote_languages);
    $this->assertEquals('en', $matching_language);

    $language = 'en';
    $remote_languages = ['en-GB-London' => 'English (London - United Kingdom)', 'en-GB' => 'English (United Kingdom)'];
    $matching_language = \Drupal::service('tmgmt.language_matcher')->getMatchingLangcode($language, $remote_languages);
    $this->assertEquals('en-GB', $matching_language);

    // No match.
    $language = 'en';
    $remote_languages = ['es' => 'Spanish'];
    $matching_language = \Drupal::service('tmgmt.language_matcher')->getMatchingLangcode($language, $remote_languages);
    $this->assertEquals('en', $matching_language);

    $language = 'en';
    $remote_languages = [];
    $matching_language = \Drupal::service('tmgmt.language_matcher')->getMatchingLangcode($language, $remote_languages);
    $this->assertEquals('en', $matching_language);
  }

}
