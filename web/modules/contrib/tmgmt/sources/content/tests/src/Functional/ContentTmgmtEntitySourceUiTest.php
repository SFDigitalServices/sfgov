<?php

namespace Drupal\Tests\tmgmt_content\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\tmgmt\Functional\TmgmtEntityTestTrait;
use Drupal\Tests\tmgmt\Functional\TMGMTTestBase;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\Translator;

/**
 * Content entity source UI tests.
 *
 * @group tmgmt
 */
class ContentTmgmtEntitySourceUiTest extends TMGMTTestBase {
  use TmgmtEntityTestTrait;
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tmgmt_content', 'comment', 'ckeditor', 'block_content');

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->addLanguage('de');
    $this->addLanguage('fr');
    $this->addLanguage('es');
    $this->addLanguage('el');
    $this->addLanguage('it');

    $this->createNodeType('page', 'Page', TRUE);
    $this->createNodeType('article', 'Article', TRUE);

    $this->loginAsAdmin(array(
      'create translation jobs',
      'submit translation jobs',
      'accept translation jobs',
      'administer blocks',
      'administer content translation',
      'edit any article content',
    ));
  }

  /**
   * Test the translate tab for a single checkout.
   */
  function testNodeTranslateTabSingleCheckout() {
    $this->loginAsTranslator(array('translate any entity', 'create content translations'));

    // Create an english source node.
    $node = $this->createTranslatableNode('page', 'en');
    // Create a nodes that will not be translated to test the missing
    // translation filter.
    $node_not_translated = $this->createTranslatableNode('page', 'en');
    $node_german = $this->createTranslatableNode('page', 'de');

    // Go to the translate tab.
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Translate');

    // Assert some basic strings on that page.
    $this->assertText(t('Translations of @title', array('@title' => $node->getTitle())));
    $this->assertText(t('Pending Translations'));

    // Request a translation for german.
    $edit = array(
      'languages[de]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText($node->getTitle());

    // Submit.
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the translate tab.
    $this->assertEqual($node->toUrl('canonical', array('absolute' => TRUE))->toString() . '/translations', $this->getUrl());
    $this->assertText(t('Test translation created.'));
    $this->assertText(t('The translation of @title to @language is finished and can now be reviewed.', array(
      '@title' => $node->getTitle(),
      '@language' => t('German')
    )));

    // Verify that the pending translation is shown.
    $this->clickLinkWithImageTitle('Needs review');
    $this->drupalPostForm(NULL, array(), t('Save as completed'));

    $node = Node::load($node->id());
    $translation = $node->getTranslation('de');
    $this->assertText(t('The translation for @title has been accepted as @target.', array('@title' => $node->getTitle(), '@target' => $translation->label())));

    // German node should now be listed and be clickable.
    $this->clickLink('de(de-ch): ' . $node->label());
    $this->assertText('de(de-ch): ' . $node->getTitle());
    $this->assertText('de(de-ch): ' . $node->body->value);

    // Test that the destination query argument does not break the redirect
    // and we are redirected back to the correct page.

    // Go to the translate tab.
    $this->drupalGet('node/' . $node->id());
    $this->clickLink(t('Translate'));
    // Request a translation for french.
    $edit = array(
      'languages[fr]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));
    $this->drupalGet('node/' . $node->id() . '/translations', array('query' => array('destination' => 'node/' . $node->id())));
    // The job item is not yet active.
    $this->clickLink(t('Inactive'));
    $this->assertText($node->getTitle());
    $this->assertRaw('<div data-drupal-selector="edit-actions" class="form-actions js-form-wrapper form-wrapper" id="edit-actions">');

    // Assert that the validation of HTML tags with editor works.
    $this->drupalPostForm(NULL, [], t('Validate HTML tags'));
    $this->assertText($node->label());
    $this->assertResponse(200);

    $this->drupalGet('node/' . $node->id() . '/translations', array('query' => array('destination' => 'node/' . $node->id())));

    // Request a spanish translation.
    $edit = array(
      'languages[es]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the checkout page.
    $this->assertText(t('One job needs to be checked out.'));
    $this->assertText($node->getTitle());
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the originally defined destination URL.
    $this->assertEqual($node->toUrl('canonical', array('absolute' => TRUE))->toString(), $this->getUrl());

    // Test the missing translation filter.
    $this->drupalGet('admin/tmgmt/sources/content/node');
    $this->assertText($node->getTitle());
    $this->assertText($node_not_translated->getTitle());
    $this->drupalPostForm(NULL, array(
      'search[target_language]' => 'de',
      'search[target_status]' => 'untranslated',
    ), t('Search'));
    $this->assertNoText($node->getTitle());
    $this->assertNoText($node_german->getTitle());
    $this->assertText($node_not_translated->getTitle());
    // Update the outdated flag of the translated node and test if it is
    // listed among sources with missing translation.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $node = Node::load($node->id());
    $node->getTranslation('de')->content_translation_outdated->value = 1;
    $node->save();
    $this->drupalPostForm(NULL, array(
      'search[target_language]' => 'de',
      'search[target_status]' => 'outdated',
    ), t('Search'));
    $this->assertText($node->getTitle());
    $this->assertNoText($node_german->getTitle());
    $this->assertNoText($node_not_translated->getTitle());

    $this->drupalPostForm(NULL, array(
      'search[target_language]' => 'de',
      'search[target_status]' => 'untranslated_or_outdated',
    ), t('Search'));
    $this->assertText($node->getTitle());
    $this->assertNoText($node_german->getTitle());
    $this->assertText($node_not_translated->getTitle());
    // Check that is set to outdated.
    $xpath = $this->xpath('//*[@id="edit-items"]/tbody/tr[2]/td[6]/a/img');
    $this->assertEqual($xpath[0]->getAttribute('title'), t('Translation Outdated'));

    // Check that the icons link to the appropriate translations.
    $xpath_source = $this->xpath('//*[@id="edit-items"]/tbody/tr[2]/td[4]/*[1]');
    $xpath_not_translated = $this->xpath('//*[@id="edit-items"]/tbody/tr[2]/td[5]/*[1]');
    $xpath_outdated = $this->xpath('//*[@id="edit-items"]/tbody/tr[2]/td[6]/*[1]');
    $this->assertTrue(strpos($xpath_source[0]->getAttribute('href'), '/node/1') !== FALSE);
    $this->assertContains('node/1', $xpath_source[0]->getAttribute('href'));
    $this->assertNotEquals('a', $xpath_not_translated[0]->getTagName());
    $this->assertContains('/de/node/1', $xpath_outdated[0]->getAttribute('href'));

    // Test that a job can not be accepted if the entity does not exist.
    $deleted_node = $this->createTranslatableNode('page', 'en');
    $second_node = $this->createTranslatableNode('page', 'en');
    $this->drupalGet('node/' . $deleted_node->id() . '/translations');
    $edit = array(
      'languages[de]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));
    $edit = array(
      'languages[fr]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));
    $this->drupalPostForm(NULL, [], t('Submit to provider'));

    $job = $this->createJob('en', 'de');
    $job->addItem('content', 'node', $deleted_node->id());
    $job->addItem('content', 'node', $second_node->id());

    $this->drupalGet($job->toUrl());
    $this->drupalPostForm(NULL, [], t('Submit to provider'));
    $this->assertText(t('1 conflicting item has been dropped.'));

    $this->drupalGet('node/' . $deleted_node->id() . '/translations');
    $this->clickLinkWithImageTitle('Needs review');

    // Delete the node and assert that the job can not be accepted.
    $deleted_node->delete();
    $this->drupalPostForm(NULL, array(), t('Save as completed'));
    $this->assertText(t('@id of type @type does not exist, the job can not be completed.', array('@id' => $deleted_node->id(), '@type' => $deleted_node->getEntityTypeId())));
  }

  /**
   * Test the translate tab for a multiple checkout.
   */
  function testNodeTranslateTabMultipleCheckout() {
    // Allow auto-accept.
    $default_translator = Translator::load('test_translator');
    $default_translator
      ->setAutoAccept(TRUE)
      ->save();

    $this->loginAsTranslator(array('translate any entity', 'create content translations'));

    // Create an english source node.
    $node = $this->createTranslatableNode('page', 'en');

    // Go to the translate tab.
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Translate');

    // Assert some basic strings on that page.
    $this->assertText(t('Translations of @title', array('@title' => $node->getTitle())));
    $this->assertText(t('Pending Translations'));

    // Request a translation for german, spanish and french.
    $edit = array(
      'languages[de]' => TRUE,
      'languages[es]' => TRUE,
      'languages[it]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('3 jobs need to be checked out.'));

    // Assert progress bar.
    $this->assertText('3 jobs pending');
    $this->assertText($node->label() . ', English to German');
    $this->assertText($node->label() . ', English to Spanish');
    $this->assertText($node->label() . ', English to Italian');
    $this->assertRaw('progress__track');
    $this->assertRaw('<div class="progress__bar" style="width: 3%"></div>');

    // Submit all jobs.
    $edit = [
      'label[0][value]' => 'Customized label',
      'submit_all' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit to provider and continue'));

    // Assert messages.
    $this->assertText('Test translation created.');
    $this->assertText('The translation job has been finished.');
    $this->assertText('The translation for ' . $node->label() . ' has been accepted as de(de-ch): ' . $node->label() . '.');
    $this->assertText('The translation for ' . $node->label() . ' has been accepted as es: ' . $node->label() . '.');
    $this->assertText('The translation for ' . $node->label() . ' has been accepted as it: ' . $node->label() . '.');

    // Make sure that we're back on the translate tab.
    $this->assertEqual($node->toUrl('canonical', array('absolute' => TRUE))->toString() . '/translations', $this->getUrl());
    $this->assertText(t('Test translation created.'));
    $this->assertNoText(t('The translation of @title to @language is finished and can now be reviewed.', array(
      '@title' => $node->getTitle(),
      '@language' => t('Spanish')
    )));

    $node = Node::load($node->id());
    $translation = $node->getTranslation('es');
    $this->assertText(t('The translation for @title has been accepted as @target.', array('@title' => $node->getTitle(), '@target' => $translation->label())));

    //Assert link is clickable.
    $this->clickLink($node->getTitle());

    // Translated nodes should now be listed and be clickable.
    // @todo Use links on translate tab.
    $this->drupalGet('de/node/' . $node->id());
    $this->assertText('de(de-ch): ' . $node->getTitle());
    $this->assertText('de(de-ch): ' . $node->body->value);

    $this->drupalGet('es/node/' . $node->id());
    $this->assertText('es: ' . $node->getTitle());
    $this->assertText('es: ' . $node->body->value);

    // Assert that all jobs were updated to use the customized label.
    foreach (Job::loadMultiple() as $job) {
      $this->assertEqual($job->label(), 'Customized label');
    }
  }

  /**
   * Test the translate tab for a quick checkout.
   */
  function testNodeTranslateTabQuickCheckout() {
    // Allow auto-accept and do not expose checkout settings.
    $default_translator = Translator::load('test_translator');
    $default_translator
      ->setSetting('expose_settings', FALSE)
      ->setAutoAccept(TRUE)
      ->save();

    $this->loginAsTranslator(['translate any entity', 'create content translations']);

    // Create an english source node.
    $node = $this->createTranslatableNode('page', 'en');

    // Go to the translate tab.
    $this->drupalGet($node->toUrl());
    $this->clickLink('Translate');

    // Request a translation for german, spanish and french.
    $edit = [
      'languages[de]' => TRUE,
      'languages[es]' => TRUE,
      'languages[it]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Request translation');

    // Assert messages.
    $this->assertText('Test translation created.');
    $this->assertText('The translation job has been finished.');
    $this->assertText('The translation for ' . $node->label() . ' has been accepted as de(de-ch): ' . $node->label() . '.');
    $this->assertText('The translation for ' . $node->label() . ' has been accepted as es: ' . $node->label() . '.');
    $this->assertText('The translation for ' . $node->label() . ' has been accepted as it: ' . $node->label() . '.');

    // Make sure that we're back on the translate tab.
    $this->assertEqual($node->toUrl('drupal:content-translation-overview', ['absolute' => TRUE])->toString(), $this->getUrl());
    $this->assertText('Test translation created.');
    $this->assertNoText(t('The translation of @title to @language is finished and can now be reviewed.', array(
      '@title' => $node->getTitle(),
      '@language' => t('Spanish')
    )));

    $node = Node::load($node->id());
    $translation = $node->getTranslation('es');
    $this->assertText(t('The translation for @title has been accepted as @target.', ['@title' => $node->getTitle(), '@target' => $translation->label()]));

    // Assert link is clickable.
    $this->clickLink($node->getTitle());

    // Translated nodes should now be listed and be clickable.
    $this->clickLink('Translate');
    $this->clickLink('de(de-ch): ' . $node->getTitle());
    $this->assertText('de(de-ch): ' . $node->getTitle());
    $this->assertText('de(de-ch): ' . $node->body->value);

    $this->drupalGet('es/node/' . $node->id());
    $this->assertText('es: ' . $node->getTitle());
    $this->assertText('es: ' . $node->body->value);
  }

  /**
   * Test job submission of multiple jobs with an unsupported language
   */
  function testNodeTranslateTabMultipleCheckoutUnsupported() {
    // Allow auto-accept.
    $default_translator = Translator::load('test_translator');
    $default_translator
      ->setAutoAccept(TRUE)
      ->save();

    $this->loginAsTranslator([
      'translate any entity',
      'create content translations'
    ]);

    // Create an english source node.
    $node = $this->createTranslatableNode('page', 'en');

    // Go to the translate tab.
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Translate');

    // Assert some basic strings on that page.
    $this->assertText(t('Translations of @title', ['@title' => $node->getTitle()]));
    $this->assertText(t('Pending Translations'));

    // Request a translation for german, spanish and french.
    $edit = [
      'languages[de]' => TRUE,
      'languages[es]' => TRUE,
      'languages[el]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('3 jobs need to be checked out.'));

    // Assert progress bar.
    $this->assertText('3 jobs pending');
    $this->assertText($node->label() . ', English to German');
    $this->assertText($node->label() . ', English to Spanish');
    $this->assertText($node->label() . ', English to Greek');
    $this->assertRaw('progress__track');
    $this->assertRaw('<div class="progress__bar" style="width: 3%"></div>');

    // Submit all jobs.
    $edit = [
      'submit_all' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit to provider and continue'));

    // Assert messages.
    $this->assertText('Test translation created.');
    $this->assertText('The translation job has been finished.');
    $this->assertText('The translation for ' . $node->label() . ' has been accepted as de(de-ch): ' . $node->label() . '.');
    $this->assertText('The translation for ' . $node->label() . ' has been accepted as es: ' . $node->label() . '.');
    $this->assertText('Job ' . $node->label() . ' is not translatable with the chosen settings: Test provider can not translate from English to Greek.');

    // Assert progress bar.
    $this->assertText('1 job pending');
    $this->assertNoText($node->label() . ', English to German');
    $this->assertNoText($node->label() . ', English to Spanish');
    $this->assertText($node->label() . ', English to Greek');
    $this->assertRaw('progress__track');
    $this->assertRaw('<div class="progress__bar" style="width: 67%"></div>');
  }

  /**
   * Test translating comments.
   */
  function testCommentTranslateTab() {
    // Allow auto-accept.
    $default_translator = Translator::load('test_translator');
    $default_translator
      ->setAutoAccept(TRUE)
      ->save();

    // Add default comment type.
    $this->addDefaultCommentField('node', 'article');

    // Enable comment translation.
    /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
    $content_translation_manager = \Drupal::service('content_translation.manager');
    $content_translation_manager->setEnabled('comment', 'comment', TRUE);

    // Change comment_body field to be translatable.
    $comment_body = FieldConfig::loadByName('comment', 'comment', 'comment_body');
    $comment_body->setTranslatable(TRUE)->save();

    // Create a user that is allowed to translate comments.
    $permissions = array_merge($this->translator_permissions, array(
      'translate comment',
      'post comments',
      'skip comment approval',
      'edit own comments',
      'access comments',
    ));
    $this->loginAsTranslator($permissions, TRUE);

    // Create an english source article.
    $node = $this->createTranslatableNode('article', 'en');

    // Add a comment.
    $this->drupalGet('node/' . $node->id());
    $edit = array(
      'subject[0][value]' => $this->randomMachineName(),
      'comment_body[0][value]' => $this->randomMachineName(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('Your comment has been posted.'));

    // Go to the translate tab.
    $this->clickLink('Edit');
    $this->assertTrue(preg_match('|comment/(\d+)/edit$|', $this->getUrl(), $matches), 'Comment found');
    $comment = Comment::load($matches[1]);
    $this->clickLink('Translate');

    // Assert some basic strings on that page.
    $this->assertText(t('Translations of @title', array('@title' => $comment->getSubject())));
    $this->assertText(t('Pending Translations'));

    // Request translations.
    $edit = array(
      'languages[de]' => TRUE,
      'languages[es]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Request translation'));

    // Verify that we are on the translate tab.
    $this->assertText(t('2 jobs need to be checked out.'));

    // Submit all jobs.
    $this->assertText($comment->getSubject());
    $this->drupalPostForm(NULL, array(), t('Submit to provider and continue'));
    $this->assertText($comment->getSubject());
    $this->drupalPostForm(NULL, array(), t('Submit to provider'));

    // Make sure that we're back on the translate tab.
    $this->assertUrl($comment->toUrl('canonical', array('absolute' => TRUE))->toString() . '/translations');
    $this->assertText(t('Test translation created.'));
    $this->assertNoText(t('The translation of @title to @language is finished and can now be reviewed.', array(
      '@title' => $comment->getSubject(),
      '@language' => t('Spanish'),
    )));

    $this->assertText(t('The translation for @title has been accepted as es: @target.', array('@title' => $comment->getSubject(), '@target' => $comment->getSubject())));

    // The translated content should be in place.
    $this->clickLink('de(de-ch): ' . $comment->getSubject());
    $this->assertText('de(de-ch): ' . $comment->get('comment_body')->value);
    $this->drupalGet('comment/1/translations');
    $this->clickLink('es: ' . $comment->getSubject());
    $this->drupalGet('es/node/' . $comment->id());
    $this->assertText('es: ' . $comment->get('comment_body')->value);
  }

  /**
   * Test the entity source specific cart functionality.
   */
  function testCart() {
    $this->loginAsTranslator(array('translate any entity', 'create content translations'));

    $nodes = array();
    for ($i = 0; $i < 4; $i++) {
      $nodes[$i] = $this->createTranslatableNode('page');
    }

    // Test the source overview.
    $this->drupalPostForm('admin/tmgmt/sources/content/node', array(
      'items[' . $nodes[1]->id() . ']' => TRUE,
      'items[' . $nodes[2]->id() . ']' => TRUE,
    ), t('Add to cart'));

    $this->drupalGet('admin/tmgmt/cart');
    $this->assertText($nodes[1]->getTitle());
    $this->assertText($nodes[2]->getTitle());

    // Test the translate tab.
    $this->drupalGet('node/' . $nodes[3]->id() . '/translations');
    $this->assertRaw(t('There are @count items in the <a href=":url">translation cart</a>.',
        array('@count' => 2, ':url' => Url::fromRoute('tmgmt.cart')->toString())));

    $this->drupalPostForm(NULL, array(), t('Add to cart'));
    $this->assertRaw(t('@count content source was added into the <a href=":url">cart</a>.', array('@count' => 1, ':url' => Url::fromRoute('tmgmt.cart')->toString())));
    $this->assertRaw(t('There are @count items in the <a href=":url">translation cart</a> including the current item.',
        array('@count' => 3, ':url' => Url::fromRoute('tmgmt.cart')->toString())));

    // Add nodes and assert that page footer is being shown.
    $nodes = array();
    for ($i = 0; $i < 50; $i++) {
      $nodes[$i] = $this->createTranslatableNode('page');
    }
    $this->drupalGet('admin/tmgmt/sources/content/node');
    $this->assertRaw('<ul class="pager__items js-pager__items">');
    $this->assertEqual(count($this->xpath('//nav[@class="pager"]/ul[@class="pager__items js-pager__items"]/li/a')), 5);
  }

  /**
   * Tests the embedded references.
   */
  function testEmbeddedReferences() {

    // Create 4 field storages, 3 for nodes, 1 for users (not translatable
    // target).
    $field1 = FieldStorageConfig::create(
        array(
          'field_name' => 'field1',
          'entity_type' => 'node',
          'type' => 'entity_reference',
          'cardinality' => -1,
          'settings' => array('target_type' => 'node'),
        )
      );
    $field1->save();
    $field2 = FieldStorageConfig::create(
      array(
        'field_name' => 'field2',
        'entity_type' => 'node',
        'type' => 'entity_reference',
        'cardinality' => -1,
        'settings' => array('target_type' => 'node'),
      )
    );
    $field2->save();
    $field3 = FieldStorageConfig::create(
      array(
        'field_name' => 'field3',
        'entity_type' => 'node',
        'type' => 'entity_reference',
        'cardinality' => -1,
        'settings' => array('target_type' => 'node'),
      )
    );
    $field3->save();
    $field4 = FieldStorageConfig::create(
      array(
        'field_name' => 'field4',
        'entity_type' => 'node',
        'type' => 'entity_reference',
        'cardinality' => -1,
        'settings' => array('target_type' => 'user'),
      )
    );
    $field4->save();

    $this->createNodeType('untranslatable', 'Untranslatable', FALSE);

    // There are two node types, article (translatable) and untranslatable, with
    // the following field configuration:
    // Untranslatable Field 1 on article and untranslatable: Available
    // Untranslatable Field 2 on untranslatable: Not Available
    // Translatable Field 3 on article: Available
    // Untranslatable Field 4 (user reference) on article: Not available.

    FieldConfig::create(
      array(
        'field_storage' => $field1,
        'bundle' => 'article',
        'label' => 'Field 1',
        'translatable' => FALSE,
        'settings' => array(),
      )
    )->save();
    FieldConfig::create(
      array(
        'field_storage' => $field1,
        'bundle' => 'untranslatable',
        'label' => 'Field 1',
        'translatable' => FALSE,
        'settings' => array(),
      )
    )->save();
    FieldConfig::create(
      array(
        'field_storage' => $field2,
        'bundle' => 'untranslatable',
        'label' => 'Field 2',
        'translatable' => FALSE,
        'settings' => array(),
      )
    )->save();

    FieldConfig::create(
      array(
        'field_storage' => $field3,
        'bundle' => 'article',
        'label' => 'Field 3',
        'translatable' => TRUE,
        'settings' => array(),
      )
    )->save();

    FieldConfig::create(
      array(
        'field_storage' => $field4,
        'bundle' => 'article',
        'label' => 'Field 4',
        'translatable' => FALSE,
        'settings' => array(),
      )
    )->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field1', [
        'type' => 'entity_reference_entity_view',
        'settings' => ['view_mode' => 'teaser'],
      ])
      ->save();

    $this->drupalGet('admin/tmgmt/settings');

    // Field 1 and 3 should be available, enable them.
    $checked_reference_fields = array(
      'embedded_fields[node][field1]' => TRUE,
      'embedded_fields[node][field3]' => TRUE,
    );

    // The node about translatable fields should be shown exactly once.
    $this->assertUniqueText('Note: This is a translatable field, embedding this will add a translation on the existing reference.');

    // String fields, field 2 and 4 as well as the node type aund uid reference
    // should not show up.
    $this->assertNoField('embedded_fields[node][title]');
    $this->assertNoField('embedded_fields[node][uid]');
    $this->assertNoField('embedded_fields[node][field2]');
    $this->assertNoField('embedded_fields[node][field4]');
    $this->assertNoField('embedded_fields[node][type]');

    $this->drupalPostForm(NULL, $checked_reference_fields, t('Save configuration'));

    // Check if the save was successful.
    $this->assertText(t('The configuration options have been saved.'));
    $this->assertFieldChecked('edit-embedded-fields-node-field1');
    $this->assertFieldChecked('edit-embedded-fields-node-field3');

    // Create translatable child node.
    $edit = [
      'title' => 'Child title',
      'type' => 'article',
      'langcode' => 'en',
    ];
    $child_node = $this->createNode($edit);

    // Create translatable parent node.
    $edit = [
      'title' => 'Parent title',
      'type' => 'article',
      'langcode' => 'en',
    ];
    $edit['field1'][]['target_id'] = $child_node->id();
    $parent_node = $this->createNode($edit);

    // Create a translation job.
    $job = $this->createJob('en', 'de');
    $job->translator = $this->default_translator->id();
    $job->save();
    $job_item = tmgmt_job_item_create('content', $parent_node->getEntityTypeId(), $parent_node->id(), array('tjid' => $job->id()));
    $job_item->save();
    $job->requestTranslation();

    // Visit preview page.
    $this->drupalGet(URL::fromRoute('entity.tmgmt_job_item.canonical', ['tmgmt_job_item' => $job_item->id()]));
    $this->clickLink(t('Preview'));

    // Check if parent and child nodes are translated.
    $this->assertText('de(de-ch): ' . $parent_node->getTitle());
    $this->assertText('de(de-ch): ' . $parent_node->body->value);
    $this->assertText('de(de-ch): ' . $child_node->getTitle());
    $this->assertText('de(de-ch): ' . $child_node->body->value);
  }

  /**
   * Test content entity source preview.
   */
  function testEntitySourcePreview() {
    // Create the basic block type.
    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
    ]);
    $bundle->save();

    // Enable translation for basic blocks.
    $edit = [
      'entity_types[block_content]' => 'block_content',
      'settings[block_content][basic][translatable]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));
    $this->assertText(t('Settings successfully updated.'));

    // Create a custom block.
    $custom_block = BlockContent::create([
      'type' => 'basic',
      'info' => 'Custom Block',
      'langcode' => 'en',
    ]);
    $custom_block->save();
    // Translate the custom block and assert the preview.
    $this->drupalPostForm('admin/tmgmt/sources/content/block_content', ['items[1]' => 1], t('Request translation'));
    $this->drupalPostForm(NULL, ['target_language' => 'de', 'translator' => 'test_translator'], t('Submit to provider'));
    $this->clickLink(t('reviewed'));
    $this->clickLink(t('Preview'));
    $this->assertText(t('Preview of Custom Block for German'));

    // Create a node and translation job.
    $node = $this->createTranslatableNode('page', 'en');
    $this->drupalPostForm('admin/tmgmt/sources', ['items[1]' => 1], t('Request translation'));
    $this->drupalPostForm(NULL, ['target_language' => 'de', 'translator' => 'test_translator'], t('Submit to provider'));

    // Delete the node.
    $node->delete();

    // Review the translation.
    $this->clickLink(t('reviewed'));
    $review_url = $this->getSession()->getCurrentUrl();;

    // Assert that preview page is not available for non-existing entities.
    $this->clickLink(t('Preview'));
    $this->assertResponse(404);

    // Assert translation message for the non-existing translated entity.
    $this->drupalPostForm($review_url, ['title|0|value[translation]' => 'test_translation'], t('Save'));
    $this->assertText(t('The translation has been saved successfully.'));

    // Create translatable node.
    $node = $this->createTranslatableNode('page', 'en');

    $job = $this->createJob('en', 'de');
    $job->translator = $this->default_translator->id();
    $job->settings->action = 'submit';
    $job->save();
    $job_item = tmgmt_job_item_create('content', $node->getEntityTypeId(), $node->id(), array('tjid' => $job->id()));
    $job_item->save();

    // At this point job is state 0 (STATE_UNPROCESSED) or "cart job", we don't
    // want a preview link available.
    $this->drupalGet(URL::fromRoute('entity.tmgmt_job_item.canonical', ['tmgmt_job_item' => $job->id()])->setAbsolute()->toString());
    $this->assertNoLink(t('Preview'));
    // Changing job state to active.
    $job->requestTranslation();

    // Visit preview route without key.
    $this->drupalGet(URL::fromRoute('tmgmt_content.job_item_preview', ['tmgmt_job_item' => $job->id()])->setAbsolute()->toString());
    $this->assertResponse(403);
    // Visit preview by clicking the preview button.
    $this->drupalGet(URL::fromRoute('entity.tmgmt_job_item.canonical', ['tmgmt_job_item' => $job->id()])->setAbsolute()->toString());
    $this->clickLink(t('Preview'));
    $this->assertResponse(200);

    // Translate job.
    $job->settings->action = 'translate';
    $job->save();
    $job->requestTranslation();
    $this->assertTitle(t("Preview of @title for @target_language | Drupal", [
      '@title' => $node->getTitle(),
      '@target_language' => $job->getTargetLanguage()->getName(),
    ]));

    // Test if anonymous user can access preview without key.
    $this->drupalLogout();
    $this->drupalGet(URL::fromRoute('tmgmt_content.job_item_preview', ['tmgmt_job_item' => $job->id()])->setAbsolute()->toString());
    $this->assertResponse(403);

    // Test if anonymous user can access preview with key.
    $key = \Drupal::service('tmgmt_content.key_access')->getKey($job_item);
    $this->drupalGet(URL::fromRoute('tmgmt_content.job_item_preview', ['tmgmt_job_item' => $job_item->id()], ['query' => ['key' => $key]]));
    $this->assertResponse(200);
    $this->assertTitle(t("Preview of @title for @target_language | Drupal", [
      '@title' => $node->getTitle(),
      '@target_language' => $job->getTargetLanguage()->getName(),
    ]));

    $this->loginAsAdmin([
      'accept translation jobs',
    ]);

    // Test preview if we edit translation.
    $this->drupalGet('admin/tmgmt/items/' . $job_item->id());
    $edit = [
      'title|0|value[translation]' => 'de(de-ch): Test title for preview translation from en to de.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('admin/tmgmt/items/' . $job_item->id());
    $this->clickLink(t('Preview'));
    $this->assertText('de(de-ch): Test title for preview translation from en to de.');

    // Test if anonymous user can see also the changes.
    $this->drupalLogout();
    $key = \Drupal::service('tmgmt_content.key_access')->getKey($job_item);
    $this->drupalGet(Url::fromRoute('tmgmt_content.job_item_preview', ['tmgmt_job_item' => $job_item->id()], ['query' => ['key' => $key]]));
    $this->assertResponse(200);
    $this->assertText('de(de-ch): Test title for preview translation from en to de.');

    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();

    // There should be no link if the job item is accepted.
    $this->drupalGet('admin/tmgmt/items/' . $node->id(), array('query' => array('destination' => 'admin/tmgmt/items/' . $node->id())));
    $this->assertNoLink(t('Preview'));
  }

  /**
   * Test content entity source anonymous access.
   */
  public function testEntitySourceAnonymousAccess() {
    // Create translatable node.
    $node = $this->createTranslatableNode('page', 'en');

    $job = $this->createJob('en', 'de');
    $job->translator = $this->default_translator->id();
    $job->save();
    $job_item = tmgmt_job_item_create('content', $node->getEntityTypeId(), $node->id(), array('tjid' => $job->id()));
    $job_item->save();

    // Anonymous view of content entities.
    $node->setUnpublished();
    $node->save();
    $this->drupalLogout();
    $url = $job_item->getSourceUrl();
    $this->drupalGet($url);
    $this->assertResponse(200);
    \Drupal::configFactory()->getEditable('tmgmt.settings')->set('anonymous_access', FALSE)->save();
    $this->drupalGet($url);
    $this->assertResponse(403);
    \Drupal::configFactory()->getEditable('tmgmt.settings')->set('anonymous_access', TRUE)->save();
    $this->drupalGet($url);
    $this->assertResponse(200);
    $job->aborted();
    $this->drupalGet($url);
    $this->assertResponse(403);
  }

  /**
   * Test the handling existing content with continuous jobs.
   */
  public function testSourceOverview() {
    // Create translatable node.
    $node = $this->createTranslatableNode('article', 'en');

    $this->drupalGet('admin/tmgmt/sources');
    $this->assertText($node->getTitle());

    // Test that there are no "Add to continuous jobs" button and checkbox.
    $this->assertSession()->elementNotExists('css', '#edit-add-to-continuous-jobs');
    $this->assertSession()->elementNotExists('css', '#edit-add-all-to-continuous-jobs');

    // Create two additional nodes.
    $this->createTranslatableNode('article', 'en');
    $this->createTranslatableNode('article', 'en');

    // Continuous settings configuration.
    $continuous_settings = [
      'content' => [
        'node' => [
          'enabled' => 1,
          'bundles' => [
            'article' => 1,
            'page' => 0,
          ],
        ],
      ],
    ];

    // Create continuous job.
    $continuous_job = $this->createJob('en', 'de', 0, [
      'label' => 'Continuous job',
      'job_type' => 'continuous',
      'continuous_settings' => $continuous_settings,
      'translator' => $this->default_translator->id(),
    ]);

    // Test that there is now "Add to continuous jobs" button and checkbox.
    $this->drupalGet('admin/tmgmt/sources');
    $this->assertSession()->elementExists('css', '#edit-add-to-continuous-jobs');
    $this->assertSession()->elementExists('css', '#edit-add-all-to-continuous-jobs');

    // Select node for adding to continuous job.
    $edit = [
      'items[' . $node->id() . ']' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Check for continuous jobs'));
    $this->assertUniqueText(t("1 continuous job item has been created."));

    $items = $continuous_job->getItems();
    $item = reset($items);
    $this->assertLinkByHref('admin/tmgmt/items/' . $item->id());

    // Test that continuous job item is created for selected node.
    $continuous_job_items = $continuous_job->getItems();
    $continuous_job_item = reset($continuous_job_items);
    $this->assertEqual($node->label(), $continuous_job_item->label(), 'Continuous job item is created for selected node.');

    // Create another translatable node.
    $second_node = $this->createTranslatableNode('page', 'en');
    $this->drupalGet('admin/tmgmt/sources');
    $this->assertText($second_node->getTitle());

    // Select second node for adding to continuous job.
    $second_edit = [
      'items[' . $second_node->id() . ']' => TRUE,
    ];
    $this->drupalPostForm(NULL, $second_edit, t('Check for continuous jobs'));
    $this->assertUniqueText(t("None of the selected sources can be added to continuous jobs."));

    // Test that no new job items are created.
    $this->assertEqual(count($continuous_job->getItems()), 1, 'There are no new job items for selected node.');

    $this->drupalGet('admin/tmgmt/sources');

    // Select all nodes for adding to continuous job.
    $add_all_edit = [
      'add_all_to_continuous_jobs' => TRUE,
    ];
    $this->drupalPostForm(NULL, $add_all_edit, t('Check for continuous jobs'));
    $this->assertUniqueText(t("2 continuous job items have been created."));

    // Test that two new job items are created.
    $this->assertEqual(count($continuous_job->getItems()), 3, 'There are two new job items for selected nodes.');

    $this->drupalGet('admin/tmgmt/sources');
    // Select all nodes for adding to continuous job.
    $add_all_edit = [
      'add_all_to_continuous_jobs' => TRUE,
    ];
    $this->drupalPostForm(NULL, $add_all_edit, t('Check for continuous jobs'));
    $this->assertUniqueText(t("None of the selected sources can be added to continuous jobs."));

    // Test that no new job items are created.
    $this->assertEqual(count($continuous_job->getItems()), 3, 'There are no new job items for selected nodes.');
  }

  /**
   * Test content entity source preview.
   */
  public function testSourceUpdate() {
    // Create translatable node.
    $node = $this->createTranslatableNode('article', 'en');

    $job = $this->createJob('en', 'de');
    $job->save();
    $job_item = tmgmt_job_item_create('content', $node->getEntityTypeId(), $node->id(), array('tjid' => $job->id()));
    $job_item->save();

    $updated_body = 'New body';
    $edit = [
      'body[0][value]' => $updated_body,
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, 'Save');
    $this->drupalGet('admin/tmgmt/items/' . $job_item->id());
    $this->assertText($updated_body, 'Source updated correctly.');
  }

  /**
   * Test consider field sequences.
   */
  public function testConsiderFieldSequences() {
    $this->createNodeType('article1', 'Article 1', TRUE, FALSE);

    for ($i = 0; $i <= 5; $i++) {
      // Create a field.
      $field_storage = FieldStorageConfig::create(array(
        'field_name' => 'field_' . $i,
        'entity_type' => 'node',
        'type' => 'text',
        'cardinality' => mt_rand(1, 5),
        'translatable' => TRUE,
      ));
      $field_storage->save();

      // Create an instance of the previously created field.
      $field = FieldConfig::create(array(
        'field_name' => 'field_' . $i,
        'entity_type' => 'node',
        'bundle' => 'article1',
        'label' => 'Field' . $i,
        'description' => $this->randomString(30),
        'widget' => array(
          'type' => 'text',
          'label' => $this->randomString(10),
        ),
      ));
      $field->save();
      $this->field_names['node']['article1'][] = 'field_' . $i;
    }

    $node = $this->createTranslatableNode('article1', 'en');

    entity_get_form_display('node', 'article1', 'default')
      ->setComponent('body', array(
        'type' => 'text_textarea_with_summary',
        'weight' => 0,
      ))
      ->setComponent('title', array(
        'type' => 'string_textfield',
        'weight' => 1,
      ))
      ->setComponent('field_1', array(
        'type' => 'string_textfield',
        'weight' => 2,
      ))
      ->setComponent('field_2', array(
        'type' => 'string_textfield',
        'weight' => 5,
      ))
      ->setComponent('field_0', array(
        'type' => 'string_textfield',
        'weight' => 6,
      ))
      ->setComponent('field_4', array(
        'type' => 'string_textfield',
        'weight' => 7,
      ))
      ->save();

    $job = $this->createJob('en', 'de');
    $job->translator = $this->default_translator->id();
    $job->addItem('content', $node->getEntityTypeId(), $node->id());
    $job->save();

    $job->requestTranslation();

    // Visit job item review page.
    $this->drupalGet(URL::fromRoute('entity.tmgmt_job_item.canonical', ['tmgmt_job_item' => $node->id()]));
    $review_elements = $this->xpath('//*[@id="edit-review"]/div');

    $ids = [];
    foreach ($review_elements as $review_element) {
      $ids[] = $review_element->getAttribute('id');
    }
    // Check are fields showing on page in desired order. Field 3 and 5 have
    // no weight set and are expected to be ordered alphabetically, at the end.
    $this->assertEqual($ids[0], 'tmgmt-ui-element-body-wrapper');
    $this->assertEqual($ids[1], 'tmgmt-ui-element-title-wrapper');
    $this->assertEqual($ids[2], 'tmgmt-ui-element-field-1-wrapper');
    $this->assertEqual($ids[3], 'tmgmt-ui-element-field-2-wrapper');
    $this->assertEqual($ids[4], 'tmgmt-ui-element-field-0-wrapper');
    $this->assertEqual($ids[5], 'tmgmt-ui-element-field-4-wrapper');
    $this->assertEqual($ids[6], 'tmgmt-ui-element-field-3-wrapper');
    $this->assertEqual($ids[7], 'tmgmt-ui-element-field-5-wrapper');
  }

}
