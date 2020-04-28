<?php

namespace Drupal\Tests\tmgmt_config\Functional;

use Drupal\Core\Url;
use Drupal\Tests\tmgmt\Functional\TmgmtEntityTestTrait;
use Drupal\Tests\tmgmt\Functional\TMGMTTestBase;
use Drupal\tmgmt\Entity\Job;
use Drupal\views\Entity\View;

/**
 * Content entity source UI tests.
 *
 * @group tmgmt
 */
class ConfigSourceUiTest extends TMGMTTestBase {
  use TmgmtEntityTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tmgmt_config', 'views', 'views_ui', 'field_ui', 'config_translation');

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->loginAsAdmin(array(
      'create translation jobs',
      'submit translation jobs',
      'accept translation jobs',
    ));

    $this->addLanguage('de');
    $this->addLanguage('it');
    $this->addLanguage('es');
    $this->addLanguage('el');

    $this->createNodeType('article', 'Article', TRUE);
  }

  /**
   * Test the node type for a single checkout.
   */
  function testNodeTypeTranslateTabSingleCheckout() {
    $this->loginAsTranslator(array('translate configuration'));

    // Go to the translate tab.
    $this->drupalGet('admin/structure/types/manage/article/translate');

    // Assert some basic strings on that page.
    $this->assertText(t('Translations of Article content type'));
    $this->assertText(t('There are 0 items in the translation cart.'));

    // Request a translation for german.
    $edit = array(
      'languages[de]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText('Article content type (English to German, Unprocessed)');

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/structure/types/manage/article/translate');

    // We are redirected back to the correct page.
    $this->drupalGet('admin/structure/types/manage/article/translate');

    // Translated languages - german should now be listed as Needs review.
    $rows = $this->xpath('//tbody/tr');
    $found = FALSE;
    foreach ($rows as $value) {
      $image = $value->find('css', 'td:nth-child(3) a img');
      if ($image && $image->getAttribute('title') == 'Needs review') {
        $found = TRUE;
        $this->assertEquals('German', $value->find('css', 'td:nth-child(2)')->getText());
      }
    }
    $this->assertTrue($found);

    // Assert that 'Source' label is displayed properly.
    $this->assertRaw('<strong>Source</strong>');

    // Verify that the pending translation is shown.
    $this->clickLinkWithImageTitle('Needs review');
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Request a spanish translation.
    $edit = array(
      'languages[es]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the checkout page.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText('Article content type (English to Spanish, Unprocessed)');
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/structure/types/manage/article/translate');

    // Translated languages should now be listed as Needs review.
    $rows = $this->xpath('//tbody/tr');
    $counter = 0;
    foreach ($rows as $element) {
      $language = $element->find('css', 'td:nth-child(2)')->getText();
      if ('Spanish' == $language || 'German' == $language) {
        $this->assertEquals('Needs review', $element->find('css', 'td:nth-child(3) a img')->getAttribute('title'));
        $counter++;
      }
    }
    $this->assertEqual($counter, 2);

    // Test that a job can not be accepted if the translator does not exist.
    // Request an italian translation.
    $edit = array(
      'languages[it]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Go back to the originally defined destination URL without submitting.
    $this->drupalGet('admin/structure/types/manage/article/translate');

    // Verify that the pending translation is shown.
    $this->clickLink(t('Inactive'));

    // Try to save, should fail because the job has no translator assigned.
    $edit = array(
      'name[translation]' => $this->randomMachineName(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Verify that we are on the checkout page.
    $this->assertResponse(200);
  }

  /**
   * Test the node type for a single checkout.
   */
  function testNodeTypeTranslateTabMultipeCheckout() {
    $this->loginAsTranslator(array('translate configuration'));

    // Go to the translate tab.
    $this->drupalGet('admin/structure/types/manage/article/translate');

    // Assert some basic strings on that page.
    $this->assertText(t('Translations of Article content type'));
    $this->assertText(t('There are 0 items in the translation cart.'));

    // Request a translation for german and spanish.
    $edit = array(
      'languages[de]' => TRUE,
      'languages[es]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('2 jobs need to be checked out.'));

    // Submit all jobs.
    $this->assertText('Article content type (English to German, Unprocessed)');
    $this->drupalPostForm(NULL, array(), t('Submit to provider and continue'));
    $this->assertText('Article content type (English to Spanish, Unprocessed)');
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the translate tab.
    $this->assertUrl('admin/structure/types/manage/article/translate');
    $this->assertText(t('Test translation created.'));
    $this->assertNoText(t('The translation of @title to @language is finished and can now be reviewed.', array(
      '@title' => 'Article',
      '@language' => t('Spanish')
    )));

    // Translated languages should now be listed as Needs review.
    $rows = $this->xpath('//tbody/tr');
    $counter = 0;
    foreach ($rows as $element) {
      $language = $element->find('css', 'td:nth-child(2)')->getText();
      if ('Spanish' == $language || 'German' == $language) {
        $this->assertEquals('Needs review', $element->find('css', 'td:nth-child(3) a img')->getAttribute('title'));
        $counter++;
      }
    }
    $this->assertEqual($counter, 2);
  }

  /**
   * Test the node type for a single checkout.
   */
  function testViewTranslateTabSingleCheckout() {
    $this->loginAsTranslator(array('translate configuration'));

    // Go to the translate tab.
    $this->drupalGet('admin/structure/views/view/content/translate');

    // Assert some basic strings on that page.
    $this->assertText(t('Translations of Content view'));
    $this->assertText(t('There are 0 items in the translation cart.'));

    // Request a translation for german.
    $edit = array(
      'languages[de]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText('Content view (English to German, Unprocessed)');

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/structure/views/view/content/translate');

    // We are redirected back to the correct page.
    $this->drupalGet('admin/structure/views/view/content/translate');

    // Translated languages should now be listed as Needs review.
    $rows = $this->xpath('//tbody/tr');
    foreach ($rows as $element) {
      if ($element->find('css', 'td:nth-child(2)')->getText() == 'German') {
        $this->assertEquals('Needs review', $element->find('css', 'td:nth-child(3) a img')->getAttribute('title'));
      }
    }

    // Verify that the pending translation is shown.
    $this->clickLinkWithImageTitle('Needs review');
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Request a spanish translation.
    $edit = array(
      'languages[es]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the checkout page.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText('Content view (English to Spanish, Unprocessed)');
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/structure/views/view/content/translate');

    // Translated languages should now be listed as Needs review.
    $rows = $this->xpath('//tbody/tr');
    $counter = 0;
    foreach ($rows as $element) {
      $language = $element->find('css', 'td:nth-child(2)')->getText();
      if ('Spanish' == $language || 'German' == $language) {
        $this->assertEquals('Needs review', $element->find('css', 'td:nth-child(3) a img')->getAttribute('title'));
        $counter++;
      }
    }
    $this->assertEquals(2, $counter);

    // Test that a job can not be accepted if the entity does not exist.
    $this->clickLinkWithImageTitle('Needs review');

    // Delete the view  and assert that the job can not be accepted.
    $view_content = View::load('content');
    $view_content->delete();

    $this->drupalPostForm(NULL, array(), t('Save as completed'));
    $this->assertText(t('@id of type @type does not exist, the job can not be completed.', array('@id' => $view_content->id(), '@type' => $view_content->getEntityTypeId())));
  }

  /**
   * Test the field config entity type for a single checkout.
   */
  function testFieldConfigTranslateTabSingleCheckout() {
    $this->loginAsAdmin(array('translate configuration'));

    // Add a continuous job.
    $job = $this->createJob('en', 'de', 1, ['job_type' => Job::TYPE_CONTINUOUS]);
    $job->save();

    // Go to sources, field configuration list.
    $this->drupalGet('admin/tmgmt/sources/config/field_config');
    $this->assertText(t('Configuration ID'));
    $this->assertText('field.field.node.article.body');

    $edit = [
      'items[field.field.node.article.body]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Add to cart'));
    $this->clickLink(t('cart'));

    $this->assertText('Body');

    $edit = [
      'target_language[]' => 'de',
    ];
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Assert that we cannot add config entities into continuous jobs.
    $this->assertNoText(t('Check for continuous jobs'));
    $this->assertNoField('add_all_to_continuous_jobs');

    // Go to the translate tab.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.body/translate');

    // Request a german translation.
    $this->drupalPostForm(NULL, array('languages[de]' => TRUE), t('Request translation'));

    // Verify that we are on the checkout page.
    $this->assertResponse(200);
    $this->assertText(t('One job needs to be checked out.'));
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Verify that the pending translation is shown.
    $this->clickLinkWithImageTitle('Needs review');
    $this->drupalPostForm(NULL, array(), t('Save as completed'));

  }
  /**
   * Test the entity source specific cart functionality.
   */
  function testCart() {
    $this->loginAsTranslator(array('translate configuration'));

    // Test the source overview.
    $this->drupalPostForm('admin/structure/views/view/content/translate', array(), t('Add to cart'));
    $this->drupalPostForm('admin/structure/types/manage/article/translate', array(), t('Add to cart'));

    // Test if the content and article are in the cart.
    $this->drupalGet('admin/tmgmt/cart');
    $this->assertLink('Content view');
    $this->assertLink('Article content type');

    // Test the label on the source overivew.
    $this->drupalGet('admin/structure/views/view/content/translate');
    $this->assertRaw(t('There are @count items in the <a href=":url">translation cart</a> including the current item.',
        array('@count' => 2, ':url' => Url::fromRoute('tmgmt.cart')->toString())));
  }

  /**
   * Test the node type for a single checkout.
   */
  function testSimpleConfiguration() {
    $this->loginAsTranslator(array('translate configuration'));

    // Go to the translate tab.
    $this->drupalGet('admin/config/system/site-information/translate');

    // Assert some basic strings on that page.
    $this->assertText(t('Translations of System information'));

    // Request a translation for german.
    $edit = array(
      'languages[de]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText('System information (English to German, Unprocessed)');

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/config/system/site-information/translate');

    // We are redirected back to the correct page.
    $this->drupalGet('admin/config/system/site-information/translate');

    // Translated languages should now be listed as Needs review.
    $rows = $this->xpath('//tbody/tr');
    $found = FALSE;
    foreach ($rows as $value) {
      $image = $value->find('css', 'td:nth-child(3) a img');
      if ($image && $image->getAttribute('title') == 'Needs review') {
        $found = TRUE;
        $this->assertEquals('German', $value->find('css', 'td:nth-child(2)')->getText());
      }
    }
    $this->assertTrue($found);

    // Verify that the pending translation is shown.
    $this->clickLinkWithImageTitle('Needs review');
    $this->drupalPostForm(NULL, array('name[translation]' => 'de_Druplicon'), t('Save'));
    $this->clickLinkWithImageTitle('Needs review');
    $this->assertText('de_Druplicon');
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Request a spanish translation.
    $edit = array(
      'languages[es]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the checkout page.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText('System information (English to Spanish, Unprocessed)');
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertUrl('admin/config/system/site-information/translate');

    // Translated languages should now be listed as Needs review.
    $rows = $this->xpath('//tbody/tr');
    $counter = 0;
    foreach ($rows as $value) {
      $image = $value->find('css', 'td:nth-child(3) a img');
      if ($image && $image->getAttribute('title') == 'Needs review') {
        $this->assertTrue(in_array($value->find('css', 'td:nth-child(2)')->getText(), ['Spanish', 'German']));
        $counter++;
      }
    }
    $this->assertEquals(2, $counter);

    // Test translation and validation tags of account settings.
    $this->drupalGet('admin/config/people/accounts/translate');

    $this->drupalPostForm(NULL, ['languages[de]' => TRUE], t('Request translation'));

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));
    $this->clickLinkWithImageTitle('Needs review');
    $this->drupalPostForm(NULL, array('user__settings|anonymous[translation]' => 'de_Druplicon'), t('Validate HTML tags'));
    $this->assertText('de_Druplicon');
    $this->drupalPostForm(NULL, array(), t('Save'));
    $this->clickLinkWithImageTitle('Needs review');
    $this->assertText('de_Druplicon');
  }

}
