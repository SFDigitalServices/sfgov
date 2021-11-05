<?php

namespace Drupal\Tests\tmgmt_content\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\tmgmt_composite_test\Entity\EntityTestComposite;

/**
 * Content entity Source with untranslatable target types unit tests.
 *
 * @group tmgmt
 */
class ContentEntitySourceUntranslatableTargetsUnitTest extends ContentEntityTestBase {

  use EntityReferenceTestTrait;
  use ContentTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'entity_reference',
    'tmgmt_composite_test',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_composite');
    $this->installSchema('node', ['node_access']);
    $this->container->get('content_translation.manager')->setEnabled('entity_test_composite', 'entity_test_composite', FALSE);
  }

  /**
   * Test extraction and saving translation for embedded references.
   */
  public function testEmbeddedReferencesUntranslatableTargets() {
    $type = NodeType::create(['type' => 'test', 'name' => 'test']);
    $type->save();
    $this->container->get('content_translation.manager')->setEnabled('node', $type->id(), TRUE);

    // Create a translatable composite entity reference fields.
    $this->createEntityReferenceField('node', $type->id(), 't_composite', 't_composite', 'entity_test_composite', 'default', [], 2);
    $this->createEntityReferenceField('node', $type->id(), 't_composite_no_embed', 't_composite_no_embed', 'entity_test_composite');
    FieldConfig::loadByName('node',  $type->id(),  't_composite')->setTranslatable(TRUE)->save();
    FieldConfig::loadByName('node',  $type->id(),  't_composite_no_embed')->setTranslatable(TRUE)->save();
    // Create a nested untranslatable composite entity reference field.
    $this->createEntityReferenceField('entity_test_composite', 'entity_test_composite', 't_nested', 't_nested', 'entity_test_composite');

    // Enable "t_composite" to be embedded.
    $this->config('tmgmt_content.settings')->set('embedded_fields.node.t_composite', TRUE)->save();

    // Create three test entities that can be referenced.
    $referenced_entities = [];
    for ($i = 0; $i < 3; $i++) {
      $referenced_values = [
        'langcode' => 'en',
        'name' => 'Referenced entity #' . $i,
      ];
      $referenced_entities[$i] = EntityTestComposite::create($referenced_values);
      $referenced_entities[$i]->save();
    }

    $referenced_entities[2]->set('t_nested', $referenced_entities[1]);
    $referenced_entities[2]->save();

    // Create a main entity.
    $node = Node::create([
      'type' => $type->id(),
      'title' => 'Example',
      't_composite' => $referenced_entities[2],
      't_composite_no_embed' => $referenced_entities[0],
    ]);
    $node->save();

    $job = tmgmt_job_create('en', 'de');
    $job->translator = 'test_translator';
    $job->save();
    $job_item = tmgmt_job_item_create('content', 'node', $node->id(), ['tjid' => $job->id()]);
    $job_item->save();

    $source_plugin = $this->container->get('plugin.manager.tmgmt.source')->createInstance('content');
    $data = $source_plugin->getData($job_item);

    // Ensure that composite non-embedded field is not in the extracted data.
    $this->assertFalse(isset($data['t_composite_no_embed'][0]['entity']));

    // Ensure some labels and structure for field 1.
    $this->assertEquals('t_composite', $data['t_composite']['#label']);
   //  $this->assertEquals('Delta #0', $data['t_composite'][0]['#label']);
    $this->assertEquals('Name', $data['t_composite'][0]['entity']['name']['#label'], 'Name');
    $this->assertEquals('Referenced entity #2', $data['t_composite'][0]['entity']['name'][0]['value']['#text']);
    $this->assertEquals('t_nested', $data['t_composite'][0]['entity']['t_nested']['#label']);
    // Data from the composite reference of the untranslated composite target
    // is embedded too.
    $this->assertEquals('Referenced entity #1', $data['t_composite'][0]['entity']['t_nested'][0]['entity']['name'][0]['value']['#text']);

    // Now request a translation.
    $job->requestTranslation();

    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();

    \Drupal::entityTypeManager()->getStorage('entity_test_composite')->resetCache();

    // Check that the translations of the composite references were duplicated
    // correctly.
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load($node->id());
    $node_translation = $node->getTranslation('de');
    $composite_en = $node->get('t_composite')->entity;
    $composite_de = $node_translation->get('t_composite')->entity;
    $this->assertNotEquals($composite_en->id(), $composite_de->id());
    $this->assertEquals('de(de-ch): Referenced entity #2', $composite_de->getName());
    $this->assertEquals('de', $composite_de->language()->getId());
    $this->assertEquals(1, count($composite_de->getTranslationLanguages()));
    $nested_en = $composite_en->get('t_nested')->entity;
    $nested_de = $composite_de->get('t_nested')->entity;
    $this->assertNotEquals($nested_en->id(), $nested_de->id());
    $this->assertEquals('de(de-ch): Referenced entity #1', $nested_de->getName());
    $this->assertEquals('de', $nested_de->language()->getId());
    $this->assertEquals(1, count($nested_de->getTranslationLanguages()));

    // Add a new composite reference and translate the entity again.
    $node = $node->getTranslation('en');
    $node->setTitle('English (update)');
    $referenced_entities[3] = EntityTestComposite::create([
      'langcode' => 'en',
      'name' => 'Referenced entity #3',
    ]);
    $node->get('t_composite')->appendItem($referenced_entities[3]);
    $node->save();
    // Create a job and accept the translation.
    $job = tmgmt_job_create('en', 'de');
    $job->translator = 'test_translator';
    $job->save();
    $job_item = tmgmt_job_item_create('content', 'node', $node->id(), ['tjid' => $job->id()]);
    $job_item->save();

    $job->requestTranslation();
    $items = $job->getItems();
    $item = reset($items);
    // Revert the translation of the first composite to the original value.
    $name_data = $item->getData(['t_composite', 0, 'entity', 'name', 0, 'value']);
    $name_data_translation = $name_data['#translation'];
    $name_data_translation['#text'] = $name_data['#text'];
    $item->addTranslatedData($name_data_translation, ['t_composite', 0, 'entity', 'name', 0, 'value']);
    $item->acceptTranslation();

    $node = Node::load($node->id());
    \Drupal::entityTypeManager()->getStorage('entity_test_composite')->resetCache();
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $this->assertEquals('de(de-ch): English (update)', $node->getTranslation('de')->label());
    $this->assertEquals(2, $node->getTranslation('de')->get('t_composite')->count());
    $this->assertEquals('de(de-ch): Referenced entity #3', $node->getTranslation('de')->get('t_composite')->get(1)->entity->getName());
    // The ID of the unchanged field item has been changed, while the actual
    // content matches the original value.
    $this->assertNotEquals($referenced_entities[2]->id(), $node->getTranslation('de')->get('t_composite')->get(0)->target_id);
    $this->assertEquals('Referenced entity #2', $node->getTranslation('de')->get('t_composite')->get(0)->entity->getName());
  }

}
