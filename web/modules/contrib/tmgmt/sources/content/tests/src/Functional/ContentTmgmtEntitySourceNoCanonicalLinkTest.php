<?php

namespace Drupal\Tests\tmgmt_content\Functional;

use Drupal\content_translation_test\Entity\EntityTestTranslatableUISkip;
use Drupal\Tests\tmgmt\Functional\TmgmtEntityTestTrait;
use Drupal\Tests\tmgmt\Functional\TMGMTTestBase;

/**
 * Test for translatable entity types with no canonical link template.
 *
 * @group tmgmt
 *
 */
class ContentTmgmtEntitySourceNoCanonicalLinkTest extends TMGMTTestBase {
  use TmgmtEntityTestTrait;
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'content_translation_test',
    'language',
    'entity_test',
    'tmgmt_content',
    );

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->loginAsAdmin(array(
      'create translation jobs',
      'submit translation jobs',
      'accept translation jobs',
      'administer content translation',
      'access content overview',
      'update content translations',
      'translate any entity',
    ));

    // Enable entity translations for entity_test_translatable_UI_skip.
    $content_translation_manager = \Drupal::service('content_translation.manager');
    $content_translation_manager->setEnabled('entity_test_translatable_UI_skip', 'entity_test_translatable_UI_skip', TRUE);

    $this->addLanguage('de');
  }

  /**
   * Tests for no canonical link templates.
   */
  public function testNoCanonicalLinkTemplate() {
    // Create an entity that has no canonical link but is translatable.
    $entity = EntityTestTranslatableUISkip::create([
      'name' => 'name english',
      'langcode' => 'en'
    ]);
    $entity->save();

    // Go to the overview page and assert that the entity label appears.
    $this->drupalGet('admin/tmgmt/sources/content/entity_test_translatable_UI_skip');
    $this->assertText($entity->label());

    // Add a translation to the entity and submit it to the provider.
    $edit = ['items[' . $entity->id() . ']' => TRUE];
    $this->drupalPostForm(NULL, $edit, 'Request translation');
    $this->drupalPostForm(NULL, NULL, 'Submit to provider');
    $this->assertText('The translation of ' . $entity->label() . ' to German is finished and can now be reviewed.');

    // Review and save the entity translation.
    $this->clickLink('reviewed');
    $this->drupalPostForm(NULL, NULL, 'Save as completed');
    $this->assertTitle($entity->label() . ' (English to German, Finished) | Drupal');
    $this->assertText('The translation for name english has been accepted as de(de-ch): name english.');
  }
}
