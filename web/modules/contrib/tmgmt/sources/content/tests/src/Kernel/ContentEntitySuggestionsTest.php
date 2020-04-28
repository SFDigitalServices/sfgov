<?php

namespace Drupal\Tests\tmgmt_content\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\tmgmt\Kernel\TMGMTKernelTestBase;

/**
 * Basic Source-Suggestions tests.
 *
 * @group tmgmt
 */
class ContentEntitySuggestionsTest extends TMGMTKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_link_content', 'link', 'tmgmt_content', 'tmgmt_test', 'content_translation', 'node', 'filter', 'entity_reference');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('node');
    $this->installConfig(['node']);
  }

  /**
   * Prepare a node to get suggestions from.
   *
   * Creates a node with two file fields. The first one is not translatable,
   * the second one is. Both fields got two files attached, where one has
   * translatable content (title and atl-text) and the other one not.
   *
   * @return object
   *   The node which is prepared with all needed fields for the suggestions.
   */
  protected function prepareTranslationSuggestions() {
    // Create an untranslatable node type.
    $untranslatable_type = NodeType::create(['type' => $this->randomMachineName()]);
    $untranslatable_type->save();

    // Create a translatable content type with fields.
    // Only the first field is a translatable reference.
    $type = NodeType::create(['type' => $this->randomMachineName()]);
    $type->save();

    $content_translation_manager = \Drupal::service('content_translation.manager');
    $content_translation_manager->setEnabled('node', $type->id(), TRUE);

    $field1 = FieldStorageConfig::create(array(
      'field_name' => 'field1',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => -1,
      'settings' => array('target_type' => 'node'),
    ));
    $field1->save();
    $field2 = FieldStorageConfig::create(array(
      'field_name' => 'field2',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => -1,
      'settings' => array('target_type' => 'node'),
    ));
    $field2->save();
    $embedded_field = FieldStorageConfig::create(array(
      'field_name' => 'embedded_field',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => -1,
      'settings' => array('target_type' => 'node'),
    ));
    $embedded_field->save();

    $this->config('tmgmt_content.settings')
      ->set('embedded_fields.node.embedded_field', TRUE)
      ->save();

    // Create field instances on the content type.
    FieldConfig::create(array(
      'field_storage' => $field1,
      'bundle' => $type->id(),
      'label' => 'Field 1',
      'translatable' => FALSE,
      'settings' => array(),
    ))->save();
    FieldConfig::create(array(
      'field_storage' => $field2,
      'bundle' => $type->id(),
      'label' => 'Field 2',
      'translatable' => TRUE,
      'settings' => array(),
    ))->save();

    FieldConfig::create(array(
      'field_storage' => $embedded_field,
      'bundle' => $type->id(),
      'label' => 'Field 2',
      'translatable' => TRUE,
      'settings' => array(),
    ))->save();

    // Create a translatable body field.
    node_add_body_field($type);
    $field = FieldConfig::loadByName('node', $type->id(), 'body');
    $field->setTranslatable(TRUE);
    $field->save();

    // Create 4 translatable nodes to be referenced.
    $references = array();
    for ($i = 0; $i < 4; $i++) {
      $references[$i] = Node::create(array(
        'title' => $this->randomMachineName(),
        'body' => $this->randomMachineName(),
        'type' => $type->id(),
      ));
      $references[$i]->save();
    }

    // Create one untranslatable node.
    $untranslatable_node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => $untranslatable_type->id(),
    ]);
    $untranslatable_node->save();

    // Create one node in a different language.
    $different_language_node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => $type->id(),
      'langcode' => 'de',
    ]);
    $different_language_node->save();

    // Create a node with two translatable and two non-translatable references.
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => $type->id(),
      'language' => 'en',
      'body' => $this->randomMachineName(),
      $field1->getName() => [
        ['target_id' => $references[0]->id()],
        ['target_id' => $references[1]->id()],
      ],
      $field2->getName() => [
        ['target_id' => $references[2]->id()],
        ['target_id' => $untranslatable_node->id()],
        ['target_id' => $different_language_node->id()],
      ],
      $embedded_field->getName() => [
        ['target_id' => $references[3]->id()],
      ],
    ]);
    $node->save();

    $link = MenuLinkContent::create([
      'link' => [['uri' => 'entity:node/' . $node->id()]],
      'title' => 'Node menu link',
      'menu_name' => 'main',
    ]);
    $link->save();
    $node->link = $link;

    // Create a second menu link that is in a different language.
    $second_link = MenuLinkContent::create([
      'link' => [['uri' => 'entity:node/' . $node->id()]],
      'title' => 'German Node menu link',
      'menu_name' => 'main',
      'langcode' => 'de'
    ]);
    $second_link->save();

    return $node;
  }

  /**
   * Test suggested entities from a translation job.
   */
  public function testSuggestions() {
    // Prepare a job and a node for testing.
    $job = $this->createJob();
    $node = $this->prepareTranslationSuggestions();
    $expected_nodes = array(
      $node->field1[0]->target_id => $node->field1[0]->target_id,
      $node->field1[1]->target_id => $node->field1[1]->target_id,
      $node->field2[0]->target_id => $node->field2[0]->target_id,
    );
    $item = $job->addItem('content', 'node', $node->id());

    // Get all suggestions and clean the list.
    $suggestions = $job->getSuggestions();
    $job->cleanSuggestionsList($suggestions);

    // There should be 4 suggestions, 3 translatable nodes and the menu link.
    $this->assertEquals(4, count($suggestions));

    foreach ($suggestions as $suggestion) {
      switch ($suggestion['job_item']->getItemType()) {
        case 'node':
          // Check for valid attributes on the node suggestions.
          $this->assertEqual($suggestion['job_item']->getWordCount(), 2, 'Two translatable words in the suggestion.');
          $this->assertEqual($suggestion['job_item']->getItemType(), 'node', 'Got a node in the suggestion.');
          $this->assertTrue(in_array($suggestion['job_item']->getItemId(), $expected_nodes), 'Node id match between node and suggestion.');
          unset($expected_nodes[$suggestion['job_item']->getItemId()]);
          break;
        case 'menu_link_content':
          // Check for valid attributes on the menu link suggestions.
          $this->assertEqual($suggestion['job_item']->getWordCount(), 3, 'Three translatable words in the suggestion.');
          $this->assertEqual($suggestion['job_item']->getItemType(), 'menu_link_content', 'Got a menu link in the suggestion.');
          $this->assertEqual($suggestion['job_item']->getItemId(), $node->link->id(), 'Menu link id match between menu link and suggestion.');
          break;
        default:
          $this->fail('Found an invalid suggestion.');
          break;
      }
      $this->assertEqual($suggestion['job_item']->getPlugin(), 'content', 'Got a content entity as plugin in the suggestion.');
      $this->assertEqual($suggestion['from_item'], $item->id());
      $job->addExistingItem($suggestion['job_item']);
    }
    // Check that we tested all expected nodes.
    $this->assertTrue(empty($expected_nodes), 'Found unexpected node suggestions.');

    // Add the suggestion to the job and re-get all suggestions.
    $suggestions = $job->getSuggestions();
    $job->cleanSuggestionsList($suggestions);

    // Check for no more suggestions.
    $this->assertEqual(count($suggestions), 0, 'Found no more suggestions.');
  }

}
