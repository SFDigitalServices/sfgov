<?php

namespace Drupal\Tests\tmgmt\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobItemInterface;

/**
 * Base class for tests.
 */
trait TmgmtTestTrait {

  /**
   * List of permissions used by loginAsAdmin().
   *
   * @var array
   */
  protected $admin_permissions = array(
    'administer languages',
    'access administration pages',
    'administer content types',
    'administer tmgmt',
  );

  /**
   * Drupal user object created by loginAsAdmin().
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user = NULL;

  /**
   * List of permissions used by loginAsTranslator().
   *
   * @var array
   */
  protected $translator_permissions = array(
      'create translation jobs',
      'submit translation jobs',
      'accept translation jobs',
    );

  /**
   * Drupal user object created by loginAsTranslator().
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translator_user = NULL;

  /**
   * The language weight for new languages.
   *
   * @var int
   */
  protected $languageWeight = 1;

  /**
   * Will create a user with admin permissions and log it in.
   *
   * @param array $additional_permissions
   *   Additional permissions that will be granted to admin user.
   * @param boolean $reset_permissions
   *   Flag to determine if default admin permissions will be replaced by
   *   $additional_permissions.
   *
   * @return object
   *   Newly created and logged in user object.
   */
  function loginAsAdmin($additional_permissions = array(), $reset_permissions = FALSE) {
    $permissions = $this->admin_permissions;

    if ($reset_permissions) {
      $permissions = $additional_permissions;
    }
    elseif (!empty($additional_permissions)) {
      $permissions = array_merge($permissions, $additional_permissions);
    }

    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
    return $this->admin_user;
  }

  /**
   * Will create a user with translator permissions and log it in.
   *
   * @param array $additional_permissions
   *   Additional permissions that will be granted to admin user.
   * @param boolean $reset_permissions
   *   Flag to determine if default admin permissions will be replaced by
   *   $additional_permissions.
   *
   * @return object
   *   Newly created and logged in user object.
   */
  function loginAsTranslator($additional_permissions = array(), $reset_permissions = FALSE) {
    $permissions = $this->translator_permissions;

    if ($reset_permissions) {
      $permissions = $additional_permissions;
    }
    elseif (!empty($additional_permissions)) {
      $permissions = array_merge($permissions, $additional_permissions);
    }

    $this->translator_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->translator_user);
    return $this->translator_user;
  }

  /**
   * Creates, saves and returns a translator.
   *
   * @return \Drupal\tmgmt\TranslatorInterface
   */
  function createTranslator(array $values = []) {
    $translator = Translator::create($values + [
      'name' => strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
      'plugin' => 'test_translator',
      'remote_languages_mappings' => [],
      'settings' => empty($values['plugin']) ? [
        'key' => $this->randomMachineName(),
        'another_key' => $this->randomMachineName(),
      ] : []
    ]);
    $this->assertEqual(SAVED_NEW, $translator->save());
    return $translator;
  }

  /**
   * Creates, saves and returns a translation job.
   *
   * @return \Drupal\tmgmt\JobInterface
   */
  function createJob($source = 'en', $target = 'de', $uid = 1, $values = array())  {
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

  /**
   * Clicks on an image link with the provided title attribute.
   *
   * @param string $title
   *   The image title.
   */
  function clickLinkWithImageTitle($title) {
    $urls = $this->xpath('//a[img[@title=:title]]', [':title' => 'Needs review']);
    if (empty($urls)) {
      $this->fail('No image link with title' . $title . ' found');
      return;
    }
    $url_target = $this->getAbsoluteUrl($urls[0]->getAttribute('href'));
    $this->drupalGet($url_target);
  }

}
