<?php

namespace Drupal\Tests\tmgmt_config\Functional;

use Drupal\Core\Url;
use Drupal\Tests\tmgmt\Functional\TmgmtEntityTestTrait;
use Drupal\Tests\tmgmt\Functional\TMGMTTestBase;
use Drupal\tmgmt\Entity\JobItem;

/**
 * Tests the user interface for entity translation lists.
 *
 * @group tmgmt
 */
class ConfigSourceListTest extends TMGMTTestBase {
  use TmgmtEntityTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tmgmt_config', 'tmgmt_content', 'config_translation', 'views', 'views_ui', 'field_ui');

  protected $nodes = array();

  function setUp() {
    parent::setUp();
    $this->loginAsAdmin();

    $this->loginAsTranslator(array('translate configuration'));

    $this->addLanguage('de');
    $this->addLanguage('it');

    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    $this->drupalCreateContentType(array(
      'type' => 'page',
      'name' => 'Page',
    ));

    $this->drupalCreateContentType(array(
      'type' => 'simplenews_issue',
      'name' => 'Newsletter issue',
    ));
  }

  function testNodeTypeSubmissions() {

    // Simple submission.
    $edit = array(
      'items[node.type.article]' => TRUE,
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/node_type', $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText(t('Article content type (English to ?, Unprocessed)'));

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/tmgmt/sources/config/node_type');

    $this->assertText(t('Test translation created.'));
    $this->assertText(t('The translation of Article content type to German is finished and can now be reviewed.'));

    // Submission of two different entity types.
    $edit = array(
      'items[node.type.article]' => TRUE,
      'items[node.type.page]' => TRUE,
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/node_type', $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    // This is still one job, unlike when selecting more languages.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText(t('Article content type and 1 more (English to ?, Unprocessed)'));
    $this->assertText(t('1 item conflict with pending item and will be dropped on submission.'));

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/tmgmt/sources/config/node_type');

    $this->assertText(t('Test translation created.'));
    $this->assertNoText(t('The translation of Article content type to German is finished and can now be reviewed.'));
    $this->assertText(t('The translation of Page content type to German is finished and can now be reviewed.'));
  }

  function testViewTranslation() {

    // Check if we have appropriate message in case there are no entity
    // translatable content types.
    $this->drupalGet('admin/tmgmt/sources/config/view');
    $this->assertText(t('View overview (Config Entity)'));

    // Request a translation for archive.
    $edit = array(
      'items[views.view.archive]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText(t('Archive view (English to ?, Unprocessed)'));

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/tmgmt/sources/config/view');

    $this->assertText(t('Test translation created.'));
    $this->assertText(t('The translation of Archive view to German is finished and can now be reviewed.'));

    // Request a translation for more archive, recent comments, content and job
    // overview.
    $edit = array(
      'items[views.view.archive]' => TRUE,
      'items[views.view.content_recent]' => TRUE,
      'items[views.view.content]' => TRUE,
      'items[views.view.tmgmt_job_overview]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText(t('Archive view and 3 more (English to ?, Unprocessed)'));
    $this->assertText(t('1 item conflict with pending item and will be dropped on submission.'));

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/tmgmt/sources/config/view');

    $this->assertText(t('Test translation created.'));
    $this->assertNoText(t('The translation of Archive view to German is finished and can now be reviewed.'));
    $this->assertText(t('The translation of Recent content view to German is finished and can now be reviewed.'));
    $this->assertText(t('The translation of Content view to German is finished and can now be reviewed.'));
    $this->assertText(t('The translation of Job overview view to German is finished and can now be reviewed.'));

    // Make sure that the Cart page works.
    $edit = array(
      'items[views.view.tmgmt_job_items]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Add to cart'));
    $this->clickLink('cart');

    // Verify that we are on the Cart page.
    $cart_tab_active = $this->xpath('//ul[@class="tabs primary"]/li[@class="is-active"]/a')[0];
    $this->assertEquals('Cart(active tab)', $cart_tab_active->getText());
    $this->assertTitle('Cart | Drupal');
    $this->assertText('Request translation');
  }

  function testNodeTypeFilter() {

    $this->drupalGet('admin/tmgmt/sources/config/node_type');
    $this->assertText(t('Content type overview (Config Entity)'));

    // Simple filtering.
    $filters = array(
      'search[name]' => '',
      'search[langcode]' => '',
      'search[target_language]' => '',
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/node_type', $filters, t('Search'));

    // Random text in the name label.
    $filters = array(
      'search[name]' => $this->randomMachineName(5),
      'search[langcode]' => '',
      'search[target_language]' => '',
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/node_type', $filters, t('Search'));
    $this->assertText(t('No source items matching given criteria have been found.'));

    // Searching for article.
    $filters = array(
      'search[name]' => 'article',
      'search[langcode]' => '',
      'search[target_language]' => '',
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/node_type', $filters, t('Search'));
    $rows = $this->xpath('//tbody/tr/td[2]/a');
    foreach ($rows as $value) {
      $this->assertEquals('Article', $value->getText());
    }

    // Searching for article, with english source language and italian target language.
    $filters = array(
      'search[name]' => 'article',
      'search[langcode]' => 'en',
      'search[target_language]' => 'it',
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/node_type', $filters, t('Search'));
    $rows = $this->xpath('//tbody/tr/td[2]/a');
    foreach ($rows as $value) {
      $this->assertEquals('Article', $value->getText());
    }

    // Searching by keywords (shorter terms).
    $filters = array(
      'search[name]' => 'art',
      'search[langcode]' => 'en',
      'search[target_language]' => 'it',
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/node_type', $filters, t('Search'));
    $rows = $this->xpath('//tbody/tr/td[2]/a');
    foreach ($rows as $value) {
      $this->assertEquals('Article', $value->getText());
    }
  }

  /**
   * Test for simple configuration translation.
   */
  function testSimpleConfigTranslation() {
    $this->loginAsTranslator(array('translate configuration'));

    // Go to the translate tab.
    $this->drupalGet('admin/tmgmt/sources/config/_simple_config');

    // Assert some basic strings on that page.
    $this->assertText(t('Simple configuration overview (Config Entity)'));

    // Request a translation for Site information settings.
    $edit = array(
      'items[system.site_information_settings]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText('System information (English to ?, Unprocessed)');

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/tmgmt/sources/config/_simple_config');

    $overview_url = Url::fromRoute('tmgmt.source_overview', array('plugin' => 'config', 'item_type' => '_simple_config'))->toString();

    // Translated languages should now be listed as Needs review.
    $url = JobItem::load(1)->toUrl()->setOption('query', ['destination' => $overview_url])->toString();
    $imgs = $this->xpath('//a[@href=:href]/img', [':href' => $url]);
    $this->assertEqual('Active job item: Needs review', $imgs[0]->getAttribute('title'));

    $this->assertText(t('Test translation created.'));
    $this->assertText('The translation of System information to German is finished and can now be reviewed.');

    // Verify that the pending translation is shown.
    $review = $this->xpath('//table[@id="edit-items"]/tbody/tr[@class="even"][1]/td[@class="langstatus-de"]/a');
    $destination = $this->getAbsoluteUrl($review[0]->getAttribute('href'));
    $this->drupalGet($destination);
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Request a translation for Account settings
    $edit = array(
      'items[entity.user.admin_form]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the checkout page.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText('Account settings (English to ?, Unprocessed)');
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/tmgmt/sources/config/_simple_config');

    // Translated languages should now be listed as Needs review.
    $links = $this->xpath('//table[@id="edit-items"]/tbody/tr/td/a');
    $this->assertEquals(2, count($links));

    // Save one translation.
    $this->drupalPostForm('admin/tmgmt/items/1', array(), t('Save as completed'));

    // Test if the filter works.
    $filters = array(
      'search[name]' => 'system',
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/_simple_config', $filters, t('Search'));

    // Check if the list has 2 rows.
    $this->assertEqual(count($this->xpath('//tbody/tr')), 2);

    $filters = array(
      'search[target_language]' => 'de',
      'search[target_status]' => 'translated',
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/_simple_config', $filters, t('Search'));

    // Just 1 simple configuration was translated.
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);

    // Filter with name and target_status.
    $filters = array(
      'search[name]' => 'settings',
      'search[target_language]' => 'de',
      'search[target_status]' => 'untranslated',
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/_simple_config', $filters, t('Search'));

    // There is 1 simple configuration untranslated with name 'settings'.
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);

    $filters = array(
      'search[name]' => 'sys',
      'search[target_language]' => 'de',
      'search[target_status]' => 'translated',
    );
    $this->drupalPostForm('admin/tmgmt/sources/config/_simple_config', $filters, t('Search'));

    // There are 2 simple configurations with name 'sys' but just 1 is translated.
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);
  }

  /**
   * Test for field configuration translation from source list.
   */
  function testFieldConfigList() {
    $this->drupalGet('admin/tmgmt/sources/config/field_config');

    // Test submission.
    $this->drupalPostForm(NULL, array('items[field.field.node.article.body]' => TRUE), t('Request translation'));
    $this->assertText(t('One job needs to be checked out.'));
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/tmgmt/sources/config/field_config');
    $this->assertText(t('Test translation created.'));
    $this->assertText(t('The translation of Body  to German is finished and can now be reviewed.'));
  }

}
