<?php

namespace Drupal\Tests\tmgmt\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobItemInterface;

/**
 * Base class for tests.
 */
abstract class TMGMTKernelTestBase extends KernelTestBase {

  /**
   * A default translator using the test translator.
   *
   * @var \Drupal\tmgmt\TranslatorInterface
   */
  protected $default_translator;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'system', 'field', 'text', 'entity_test', 'language', 'locale', 'tmgmt', 'tmgmt_test', 'options');

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('tmgmt_job');
    $this->installEntitySchema('tmgmt_job_item');
    $this->installEntitySchema('tmgmt_message');

    $this->default_translator = Translator::create([
      'name' => 'test_translator',
      'plugin' => 'test_translator',
      'remote_languages_mappings' => [],
    ]);
    $this->default_translator->save();

    $this->addLanguage('de');
  }

  /**
   * Creates, saves and returns a translator.
   *
   * @return \Drupal\tmgmt\TranslatorInterface
   */
  function createTranslator() {
    $translator = Translator::create([
      'name' => strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
      'plugin' => 'test_translator',
      'remote_languages_mappings' => [],
      'settings' => [
        'key' => $this->randomMachineName(),
        'another_key' => $this->randomMachineName(),
      ],
    ]);
    $this->assertEquals(SAVED_NEW, $translator->save());
    return $translator;
  }

  /**
   * Creates, saves and returns a translation job.
   *
   * @param string $source
   *   The source langcode.
   * @param string $target
   *   The target langcode.
   * @param int $uid
   *   The user ID.
   * @param array $values
   *   (Optional) An array of additional entity values.
   *
   * @return \Drupal\tmgmt\JobInterface A new job.
   *   A new job.
   */
  protected function createJob($source = 'en', $target = 'de', $uid = 0, array $values = array()) {
    $job = tmgmt_job_create($source, $target, $uid, $values);
    $this->assertEqual(SAVED_NEW, $job->save());

    // Assert that the translator was assigned a tid.
    $this->assertTrue($job->id() > 0);
    return $job;
  }

  /**
   * Sets the proper environment.
   *
   * Currently just adds a new language.
   *
   * @param string $langcode
   *   The language code.
   */
  function addLanguage($langcode) {
    $language = ConfigurableLanguage::createFromLangcode($langcode);
    $language->save();
  }

  /**
   * Asserts job item language codes.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   Job item to check.
   * @param string $expected_source_lang
   *   Expected source language.
   * @param array $actual_lang_codes
   *   Expected existing language codes (translations).
   */
  function assertJobItemLangCodes(JobItemInterface $job_item, $expected_source_lang, array $actual_lang_codes) {
    $this->assertEqual($job_item->getSourceLangCode(), $expected_source_lang);
    $existing = $job_item->getExistingLangCodes();
    sort($existing);
    sort($actual_lang_codes);
    $this->assertEqual($existing, $actual_lang_codes);
  }

}
