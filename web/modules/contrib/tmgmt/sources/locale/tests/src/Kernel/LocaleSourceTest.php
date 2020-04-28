<?php

namespace Drupal\Tests\tmgmt_locale\Kernel;

use Drupal\locale\Gettext;
use Drupal\Tests\tmgmt\Kernel\TMGMTKernelTestBase;

/**
 * Basic Locale Source tests.
 *
 * @group tmgmt
 */
class LocaleSourceTest extends TMGMTKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tmgmt_locale');

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->langcode = 'de';
    $this->context = 'default';

    \Drupal::service('router.builder')->rebuild();
    $this->installSchema('locale', array('locales_source', 'locales_target'));

    file_unmanaged_copy(drupal_get_path('module', 'tmgmt_locale') . '/tests/test.de.po', 'translations://', FILE_EXISTS_REPLACE);
    $file = new \stdClass();
    $file->uri =  \Drupal::service('file_system')->realpath(drupal_get_path('module', 'tmgmt_locale') . '/tests/test.xx.po');
    $file->langcode = $this->langcode;
    Gettext::fileToDatabase($file, array());
    $this->addLanguage('es');
  }

  /**
   *  Tests translation of a locale singular term.
   */
  function testSingularTerm() {
    // Obtain one locale string with translation.
    $locale_object = db_query('SELECT * FROM {locales_source} WHERE source = :source LIMIT 1', array(':source' => 'Hello World'))->fetchObject();
    $source_text = $locale_object->source;

    // Create the new job and job item.
    $job = $this->createJob();
    $job->translator = $this->default_translator->id();
    $job->settings = array();
    $job->save();

    $item1 = $job->addItem('locale', 'default', $locale_object->lid);

    // Check the structure of the imported data.
    $this->assertEqual($item1->getItemId(), $locale_object->lid, 'Locale Strings object correctly saved');
    $this->assertEqual('Locale', $item1->getSourceType());
    $this->assertEqual('Hello World', $item1->getSourceLabel());
    $job->requestTranslation();

    foreach ($job->getItems() as $item) {
      /* @var $item JobItemInterface */
      $item->acceptTranslation();
      $this->assertTrue($item->isAccepted());
      // The source is now available in en and de.
      $this->assertJobItemLangCodes($item, 'en', array('en', 'de'));
    }

    // Check string translation.
    $expected_translation = $job->getTargetLangcode() . '(' . $job->getRemoteTargetLanguage() . '): ' . $source_text;
    $this->assertTranslation($locale_object->lid, 'de', $expected_translation);

    // Translate the german translation to spanish.
    $target_langcode = 'es';
    $job = $this->createJob('de', $target_langcode);
    $job->translator = $this->default_translator->id();
    $job->settings = array();
    $job->save();

    $item1 = $job->addItem('locale', 'default', $locale_object->lid);
    $this->assertEqual('Locale', $item1->getSourceType());
    $this->assertEqual($expected_translation, $item1->getSourceLabel());
    $job->requestTranslation();

    foreach ($job->getItems() as $item) {
      /* @var $item JobItemInterface */
      $item->acceptTranslation();
      $this->assertTrue($item->isAccepted());

      // The source should be now available for en, de and es languages.
      $this->assertJobItemLangCodes($item, 'en', array('en', 'de', 'es'));
    }

    // Check string translation.
    $this->assertTranslation($locale_object->lid, $target_langcode, $job->getTargetLangcode() . ': ' . $expected_translation);
  }

  /**
  +   * Test if the source is able to pull content in requested language.
  +   */
  function testRequestDataForSpecificLanguage() {
    $this->addLanguage('cs');

    $locale_object = db_query('SELECT * FROM {locales_source} WHERE source = :source LIMIT 1', array(':source' => 'Hello World'))->fetchObject();

    $plugin = $this->container->get('plugin.manager.tmgmt.source')->createInstance('locale');
    $reflection_plugin = new \ReflectionClass('\Drupal\tmgmt_locale\Plugin\tmgmt\Source\LocaleSource');
    $updateTranslation = $reflection_plugin->getMethod('updateTranslation');
    $updateTranslation->setAccessible(TRUE);

    $updateTranslation->invoke($plugin, $locale_object->lid, 'de', 'de translation');

    // Create the new job and job item.
    $job = $this->createJob('de', 'cs');
    $job->save();
    $job->addItem('locale', 'default', $locale_object->lid);

    $data = $job->getData();
    $this->assertEqual($data[1]['singular']['#text'], 'de translation');

    // Create new job item with a source language for which the translation
    // does not exit.
    $job = $this->createJob('es', 'cs');
    $job->save();
    try {
      $job->addItem('locale', 'default', $locale_object->lid);
      $this->fail('The job item should not be added as there is no translation for language "es"');
    }
    catch (\Exception $e) {
      $languages = \Drupal::languageManager()->getLanguages();
      // @todo Job item id missing because it is not saved yet.
      $this->assertEqual(t('Unable to load %language translation for the locale %id',
        array('%language' => $languages['es']->getName(), '%id' => $locale_object->lid)), $e->getMessage());
    }
  }

  /**
   * Verifies that strings that need escaping are correctly identified.
   */
  function testEscaping() {
    $lid = \Drupal::database()->insert('locales_source')
      ->fields(array(
        'source' => '@place-holders need %to be !esc_aped.',
        'context' => '',
      ))
      ->execute();
    $job = $this->createJob('en', 'de');
    $job->translator = $this->default_translator->id();
    $job->settings = array();
    $job->save();

    $item = $job->addItem('locale', 'default', $lid);
    $data = $item->getData();
    $expected_escape = array(
      0 => array('string' => '@place-holders'),
      20 => array('string' => '%to'),
      27 => array('string' => '!esc_aped'),
    );
    $this->assertEqual($data['singular']['#escape'], $expected_escape);

    // Invalid patterns that should be ignored.
    $lid = \Drupal::database()->insert('locales_source')
      ->fields(array(
        'source' => '@ % ! example',
        'context' => '',
      ))
      ->execute();

    $item = $job->addItem('locale', 'default', $lid);
    $data = $item->getData();
    $this->assertTrue(empty($data[$lid]['#escape']));

  }

  /**
   * Tests that system behaves correctly with an non-existing locales.
   */
  function testInexistantSource() {
    // Create inexistant locale object.
    $locale_object = new \stdClass();
    $locale_object->lid = 0;

    // Create the job.
    $job = $this->createJob();
    $job->translator = $this->default_translator->id();
    $job->settings = array();
    $job->save();

    // Create the job item.
    try {
      $job->addItem('locale', 'default', $locale_object->lid);
      $this->fail('Job item add with an inexistant locale.');
    }
    catch (\Exception $e) {
      $this->pass('Exception thrown when trying to translate non-existing locale string');
    }

    // Try to translate a source string without translation from german to
    // spanish.
    $lid = \Drupal::database()->insert('locales_source')
      ->fields(array(
        'source' => 'No translation',
        'context' => '',
      ))
      ->execute();
    $job = $this->createJob('de', 'fr');
    $job->translator = $this->default_translator->id();
    $job->settings = array();
    $job->save();

    try {
      $job->addItem('locale', 'default', $lid);
      $this->fail('Job item add with an non-existing locale did not fail.');
    }
    catch (\Exception $e) {
      $this->pass('Job item add with an non-existing locale did fail.');
    }
  }

  /**
   * Asserts a locale translation.
   *
   * @param int $lid
   *   The locale source id.
   * @param string $target_langcode
   *   The target language code.
   * @param string $expected_translation
   *   The expected translation.
   */
  public function assertTranslation($lid, $target_langcode, $expected_translation) {
    $actual_translation = db_query('SELECT * FROM {locales_target} WHERE lid = :lid AND language = :language', array(
      ':lid' => $lid,
      ':language' => $target_langcode
    ))->fetch();
    $this->assertEqual($actual_translation->translation, $expected_translation);
    $this->assertEqual($actual_translation->customized, LOCALE_CUSTOMIZED);
  }
}
