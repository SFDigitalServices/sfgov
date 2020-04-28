<?php

namespace Drupal\tmgmt_composite_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTestMul;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_composite",
 *   label = @Translation("Test entity - composite relationship"),
 *   base_table = "translatable_entity_test",
 *   data_table = "translatable_entity_test_data",
 *   entity_revision_parent_type_field = "parent_type",
 *   translatable = TRUE,
 *   content_translation_ui_skip = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class EntityTestComposite extends EntityTestMul {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['parent_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent type'))
      ->setDescription(t('The entity parent type to which this entity is referenced.'));

    return $fields;
  }

}
