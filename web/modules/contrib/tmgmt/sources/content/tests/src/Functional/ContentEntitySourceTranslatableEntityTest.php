<?php

namespace Drupal\Tests\tmgmt_content\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\tmgmt\Functional\TmgmtEntityTestTrait;
use Drupal\Tests\tmgmt\Functional\TMGMTTestBase;
use Drupal\tmgmt_composite_test\Entity\EntityTestComposite;

/**
 * Tests always embedded entity reference fields.
 *
 * @group tmgmt
 */
class ContentEntitySourceTranslatableEntityTest extends TMGMTTestBase {
  use TmgmtEntityTestTrait;
  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'field',
    'entity_reference',
    'tmgmt_composite_test',
    'tmgmt_content',
  );

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->addLanguage('de');

    $this->loginAsAdmin(['administer tmgmt']);

    // Create article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Enable entity translations for entity_test_composite and node.
    $content_translation_manager = \Drupal::service('content_translation.manager');
    $content_translation_manager->setEnabled('entity_test_composite', 'entity_test_composite', TRUE);
    $content_translation_manager->setEnabled('node', 'article', TRUE);
  }

  /**
   * Tests that the referenced entities are always embedded.
   */
  public function testTranslatableEntityReferences() {

    // Assert there is NO embedded references yet.
    $this->drupalGet('/admin/tmgmt/settings');
    $xpath = '//*[@id="edit-content"]';
    $embedded_entity = '<label for="edit-always-embedded">Always embedded</label>';
    $embedded_node = '<span class="fieldset-legend">Content</span>';
    $this->assertNoText('Authored by (User)');
    $this->assertNotContains($embedded_entity, $this->xpath($xpath)[0]->getOuterHtml());
    $this->assertNotContains($embedded_node, $this->xpath($xpath)[0]->getOuterHtml());

    // Create the reference field to the composite entity test.
    $this->createEntityReferenceField('node', 'article', 'entity_test_composite', 'entity_test_composite', 'entity_test_composite');
    FieldConfig::loadByName('node', 'article', 'entity_test_composite')->setTranslatable(FALSE)->save();

    // Assert there IS the entity_test_composite as entity embedded now.
    $this->drupalGet('/admin/tmgmt/settings');
    $this->assertText('Content: entity_test_composite');
    $this->assertContains($embedded_entity, $this->xpath($xpath)[0]->getOuterHtml());

    // Create the composite entity test.
    $composite = EntityTestComposite::create(array(
      'name' => 'composite name',
    ));
    $composite->save();

    // Create a node with a reference to the composite entity test.
    $node = $this->createNode(array(
      'title' => 'node title',
      'type' => 'article',
      'entity_test_composite' => $composite,
    ));

    // Create a job and job item for the node.
    $job = $this->createJob();
    $job->save();
    $job_item = tmgmt_job_item_create('content', $node->getEntityTypeId(), $node->id(), ['tjid' => $job->id()]);
    $job_item->save();

    // Get the data and check it contains the data for the composite entity.
    $data = $job_item->getData();
    $this->assertTrue(isset($data['entity_test_composite']));
    $this->assertEqual($data['entity_test_composite']['#label'], 'entity_test_composite');
    $this->assertFalse(isset($data['entity_test_composite'][0]['#label']));
    $this->assertEqual($data['entity_test_composite'][0]['entity']['name']['#label'], 'Name');
    $this->assertEqual($data['entity_test_composite'][0]['entity']['name'][0]['value']['#text'], 'composite name');

    // Ensure that only Content is shown in the source select form.
    $this->drupalGet('/admin/tmgmt/sources');
    $this->assertOption('edit-source', 'content:node');
    $this->assertNoOption('edit-source', 'content:entity_test_composite');

    // Now request a translation and save it back.
    $job->translator = $this->default_translator->id();
    $job->requestTranslation();
    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();

    // Load existing node and test translating
    $node = Node::load($node->id());
    $translation = $node->getTranslation('de');
    // The IDs of the composite entities did not change.
    $this->assertEquals($node->get('entity_test_composite')->target_id, $translation->get('entity_test_composite')->target_id);
    $composite = EntityTestComposite::load($translation->get('entity_test_composite')->target_id);
    $composite = $composite->getTranslation('de');
    $this->assertEqual('de(de-ch): composite name', $composite->label());
  }

  /**
   * Tests UI of the embedded entities with untranslatable target types.
   */
  public function testTranslatableEntityReferencesUntranslatableTarget() {
    // Disable entity translations for entity_test_composite.
    \Drupal::service('content_translation.manager')->setEnabled('entity_test_composite', 'entity_test_composite', FALSE);
    // Create the translatable reference field to the composite entity test.
    $this->createEntityReferenceField('node', 'article', 'entity_test_t_composite', 'entity_test_t_composite', 'entity_test_composite');

    // Assert there is the entity_test_composite as entity embedded now.
    $this->drupalGet('/admin/tmgmt/settings');
    $this->assertFieldByName('embedded_fields[node][entity_test_t_composite]');
    $this->assertText('Note: This is a translatable field to an untranslatable target. A copy of the target will be created when translating.');
  }
}

