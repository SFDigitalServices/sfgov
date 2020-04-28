<?php

namespace Drupal\Tests\tmgmt\Functional;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;


/**
 * Utility test case class with helper methods to create entities and their
 * fields with populated translatable content. Extend this class if you create
 * tests in which you need Drupal entities and/or fields.
 */
trait TmgmtEntityTestTrait {

  use CommentTestTrait;

  public $field_names = array();

  /**
   * Creates node type with several text fields with different cardinality.
   *
   * Internally it calls TMGMTEntityTestCaseUtility::attachFields() to create
   * and attach fields to newly created bundle. You can than use
   * $this->field_names['node']['YOUR_BUNDLE_NAME'] to access them.
   *
   * @param string $machine_name
   *   Machine name of the node type.
   * @param string $human_name
   *   Human readable name of the node type.
   * @param bool $translation
   *   TRUE if translation for this enitty type should be enabled.
   * pparam bool $attach_fields
   *   (optional) If fields with the same translatability should automatically
   *   be attached to the node type.
   */
  function createNodeType($machine_name, $human_name, $translation = FALSE, $attach_fields = TRUE) {
    $type = $this->drupalCreateContentType(array('type' => $machine_name, 'name' => $human_name));

    // Push in also the body field.
    $this->field_names['node'][$machine_name][] = 'body';

    if (\Drupal::hasService('content_translation.manager') && $translation) {
      $content_translation_manager = \Drupal::service('content_translation.manager');
      $content_translation_manager->setEnabled('node', $machine_name, TRUE);
    }

    if ($attach_fields) {
      $this->attachFields('node', $machine_name, $translation);
    }
    else {
    // Change body field to be translatable.
      $body = FieldConfig::loadByName('node', $machine_name, 'body');
      $body->setTranslatable(TRUE);
      $body->save();
    }
  }

  /**
   * Creates taxonomy vocabulary with custom fields.
   *
   * To create and attach fields it internally calls
   * TMGMTEntityTestCaseUtility::attachFields(). You can than access these
   * fields calling $this->field_names['node']['YOUR_BUNDLE_NAME'].
   *
   * @param string $vid
   *   Vocabulary id.
   * @param string $human_name
   *   Vocabulary human readable name.
   * @param bool|array $fields_translatable
   *   Flag or definition array to determine which or all fields should be
   *   translatable.
   *
   * @return stdClass
   *   Created vocabulary object.
   */
  function createTaxonomyVocab($machine_name, $human_name, $fields_translatable = TRUE) {
    $vocabulary = Vocabulary::create([
      'name' => $human_name,
      'vid' => $machine_name,
    ]);
    $vocabulary->save();

    $this->attachFields('taxonomy_term', $vocabulary->id(), $fields_translatable);

    return $vocabulary;
  }

  /**
   * Creates fields of type text and text_with_summary of different cardinality.
   *
   * It will attach created fields to provided entity name and bundle.
   *
   * Field names will be stored in $this->field_names['entity']['bundle']
   * through which you can access them.
   *
   * @param string $entity_name
   *   Entity name to which fields should be attached.
   * @param string $bundle
   *   Bundle name to which fields should be attached.
   * @param bool|array $translatable
   *   Flag or definition array to determine which or all fields should be
   *   translatable.
   */
  function attachFields($entity_name, $bundle, $translatable = TRUE) {
    // Create several text fields.
    $field_types = array('text', 'text_with_summary');

    for ($i = 0 ; $i <= 5; $i++) {
      $field_type = $field_types[array_rand($field_types, 1)];
      $field_name = mb_strtolower($this->randomMachineName());

      // Create a field.
      $field_storage = FieldStorageConfig::create(array(
        'field_name' => $field_name,
        'entity_type' => $entity_name,
        'type' => $field_type,
        'cardinality' => mt_rand(1, 5),
        'translatable' => is_array($translatable) && isset($translatable[$i]) ? $translatable[$i] : (boolean) $translatable,
      ));
      $field_storage->save();

      // Create an instance of the previously created field.
      $field = FieldConfig::create(array(
        'field_name' => $field_name,
        'entity_type' => $entity_name,
        'bundle' => $bundle,
        'label' => $this->randomMachineName(10),
        'description' => $this->randomString(30),
        'widget' => array(
          'type' => $field_type == 'text' ? 'text_textfield' : 'text_textarea_with_summary',
          'label' => $this->randomString(10),
        ),
      ));
      $field->save();

      // Store field names in case there are needed outside this method.
      $this->field_names[$entity_name][$bundle][] = $field_name;
    }
  }

  /**
   * Creates a node of a given bundle.
   *
   * It uses $this->field_names to populate content of attached fields.
   *
   * @param string $bundle
   *   Node type name.
   * @param string $sourcelang
   *   Source lang of the node to be created.
   *
   * @return \Drupal\node\NodeInterface
   *   Newly created node object.
   */
  protected function createTranslatableNode($bundle, $sourcelang = 'en') {
    $node = array(
      'type' => $bundle,
      'langcode' => $sourcelang,
    );

    foreach ($this->field_names['node'][$bundle] as $field_name) {
      // @todo: Why are some missing?
      if ($field_storage_config = FieldStorageConfig::loadByName('node', $field_name)) {
        $cardinality = $field_storage_config->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED ? 1 : $field_storage_config->getCardinality();

        // Create two deltas for each field.
        for ($delta = 0; $delta <= $cardinality; $delta++) {
          $node[$field_name][$delta]['value'] = $this->randomMachineName(20);
          $node[$field_name][$delta]['format'] = 'plain_text';
          if ($field_storage_config->getType() == 'text_with_summary') {
            $node[$field_name][$delta]['summary'] = $this->randomMachineName(10);
          }
        }
      }
    }

    return $this->drupalCreateNode($node);
  }

  /**
   * Creates a taxonomy term of a given vocabulary.
   *
   * It uses $this->field_names to populate content of attached fields. You can
   * access fields values using
   * $this->field_names['taxonomy_term'][$vocabulary->id(].
   *
   * @param object $vocabulary
   *   Vocabulary object for which the term should be created.
   *
   * @return object
   *   Newly created node object.
   */
  function createTaxonomyTerm(VocabularyInterface $vocabulary) {
    $term = Term::create(array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'langcode' => 'en',
    ));

    foreach ($this->field_names['taxonomy_term'][$vocabulary->id()] as $field_name) {
      $field_definition = $term->getFieldDefinition($field_name);
      $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED ? 1 : $field_definition->getCardinality();
      $field_lang = $field_definition->isTranslatable() ? 'en' : LanguageInterface::LANGCODE_DEFAULT;

      // Create two deltas for each field.
      for ($delta = 0; $delta <= $cardinality; $delta++) {
        $term->getTranslation($field_lang)->get($field_name)->get($delta)->value = $this->randomMachineName(20);
        if ($field_definition->getType() == 'text_with_summary') {
          $term->getTranslation($field_lang)->get($field_name)->get($delta)->summary = $this->randomMachineName(10);
        }
      }
    }

    $term->save();
    return $term;
  }

}
