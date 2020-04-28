<?php

namespace Drupal\Tests\tmgmt\Functional;

use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;

/**
 * Verifies functionality of translator handling
 *
 * @group tmgmt
 */
class TranslatorTest extends TMGMTTestBase {

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
   * Tests creating and deleting a translator.
   */
  function testTranslatorHandling() {
    // Create a translator for later deletion.
    $translator = parent::createTranslator();
    // Does the translator exist in the listing?
    $this->drupalGet('admin/tmgmt/translators');
    $this->assertText($translator->label());
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);

    // Create job, attach to the translator and activate.
    $job = $this->createJob();
    $job->settings = array();
    $job->save();
    $job->setState(Job::STATE_ACTIVE);
    $item = $job->addItem('test_source', 'test', 1);
    $this->drupalGet('admin/tmgmt/items/' . $item->id());
    $this->assertText(t('(Undefined)'));
    $job->translator = $translator;
    $job->save();

    // Try to delete the translator, should fail because of active job.
    $delete_url = '/admin/tmgmt/translators/manage/' . $translator->id() . '/delete';
    $this->drupalGet($delete_url);
    $this->assertResponse(403);

    // Create a continuous job.
    $continuous = $this->createJob('en', 'de', 1, ['label' => 'Continuous', 'job_type' => Job::TYPE_CONTINUOUS]);
    $continuous->translator = $translator;
    $continuous->save();

    // Delete a provider using an API call and assert that active job and its
    // job item used by deleted translator were aborted.
    $translator->delete();
    /** @var \Drupal\tmgmt\JobInterface $job */
    $job = Job::load($job->id());
    $continuous = Job::load($continuous->id());
    $this->assertEqual($job->getState(), Job::STATE_ABORTED);
    $item = $job->getMostRecentItem('test_source', 'test', 1);
    $this->assertEqual($item->getState(), JobItem::STATE_ABORTED);
    $this->assertEqual($continuous->getState(), Job::STATE_ABORTED);

    // Delete a finished job.
    $translator = parent::createTranslator();
    $job = $this->createJob();
    $job->translator = $translator;
    $item = $job->addItem('test_source', 'test', 2);
    $job->setState(Job::STATE_FINISHED);
    $job->set('label', $job->label());
    $job->save();
    $delete_url = '/admin/tmgmt/translators/manage/' . $translator->id() . '/delete';
    $this->drupalPostForm($delete_url, array(), 'Delete');
    $this->assertText(t('Add provider'));
    // Check if the list of translators has 1 row.
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);
    $this->assertText(t('@label has been deleted.', array('@label' => $translator->label())));

    // Check if the clone action works.
    $this->clickLink('Clone');
    $edit = array(
      'name' => $translator->id() . '_clone',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    // Check if the list of translators has 2 row.
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);
    $this->assertText('configuration has been created');
    // Assert that the job works and there is a text saying that the translator
    // is missing.
    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());
    $this->assertText(t('The job has no provider assigned.'));

    // Assert that also the job items are working.
    $this->drupalGet('admin/tmgmt/items/' . $item->id());
    $this->assertText(t('(Missing)'));

    // Testing the translators form with no installed translator plugins.
    // Uninstall the test module (which provides a translator).
    \Drupal::service('module_installer')->uninstall(array('tmgmt_test'), FALSE);

    // Assert that job deletion works correctly.
    \Drupal::service('module_installer')->install(array('tmgmt_file'), FALSE);
    $this->drupalPostForm('/admin/tmgmt/jobs/' . $job->id() . '/delete', [], t('Delete'));
    $this->assertResponse(200);
    $this->assertText(t('The translation job @value has been deleted.', array('@value' => $job->label())));
    \Drupal::service('module_installer')->uninstall(array('tmgmt_file'), FALSE);

    // Get the overview.
    $this->drupalGet('admin/tmgmt/translators');
    $this->assertNoText(t('Add provider'));
    $this->assertText(t('There are no provider plugins available. Please install a provider plugin.'));
  }

  /**
   * Tests remote languages mappings support in the tmgmt core.
   */
  public function testRemoteLanguagesMappings() {
    $mappings = $this->default_translator->getRemoteLanguagesMappings();
    $this->assertEqual($mappings, array(
      'en' => 'en-us',
      'de' => 'de-ch',
      'el' => 'el',
      'es' => 'es',
    ));

    $this->assertEqual($this->default_translator->mapToRemoteLanguage('en'), 'en-us');
    $this->assertEqual($this->default_translator->mapToRemoteLanguage('de'), 'de-ch');

    $remote_language_mappings = $this->default_translator->get('remote_languages_mappings');
    $remote_language_mappings['de'] = 'de-de';
    $remote_language_mappings['en'] = 'en-uk';
    $this->default_translator->set('remote_languages_mappings', $remote_language_mappings);
    $this->default_translator->save();

    $this->assertEqual($this->default_translator->mapToRemoteLanguage('en'), 'en-uk');
    $this->assertEqual($this->default_translator->mapToRemoteLanguage('de'), 'de-de');

    // Test the fallback.
    $this->container->get('state')->set('tmgmt_test_translator_map_languages', FALSE);
    $this->container->get('plugin.manager.tmgmt.translator')->clearCachedDefinitions();

    $this->assertEqual($this->default_translator->mapToRemoteLanguage('en'), 'en');
    $this->assertEqual($this->default_translator->mapToRemoteLanguage('de'), 'de');
  }

}
