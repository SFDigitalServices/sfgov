<?php

namespace Drupal\sfgov_formio\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\key_value_field\Plugin\Field\FieldType\KeyValueLongItem;

/**
 * Defines the 'formio_key_value_item' field type.
 *
 * @FieldType(
 *   id = "formio_key_value_item",
 *   label = @Translation("Key / Value (formio)"),
 *   category = @Translation("Key / Value"),
 *   default_widget = "formio_key_value_widget",
 *   default_formatter = "key_value",
 *   column_groups = {
 *     "key" = {
 *       "label" = @Translation("Key"),
 *       "translatable" = FALSE,
 *     },
 *     "value" = {
 *       "label" = @Translation("Value"),
 *       "translatable" = TRUE,
 *     },
 *     "description" = {
 *       "label" = @Translation("Description"),
 *       "translatable" = FALSE,
 *     },
 *     "label" = {
 *       "label" = @Translation("Label"),
 *       "translatable" = FALSE,
 *     },
 *     "nested_location" = {
 *       "label" = @Translation("Nested Location"),
 *       "translatable" = FALSE,
 *     },
 *   },
 * )
 */
class FormioKeyValueItem extends KeyValueLongItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns'] += [
      'label' => [
        'description' => 'Stores the human readable label',
        'type' => 'varchar',
        'length' => 255,
      ],
      'nested_location' => [
        'description' => 'Stores a reference to the formio page location',
        'type' => 'varchar',
        'length' => 255,
      ],
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Add the property definition for the label field.
      'label' => DataDefinition::create('string')
        ->setLabel(new TranslatableMarkup('Label'))
        ->setRequired(FALSE),
      // Add the property definition for the nested_location field.
      'nested_location' => DataDefinition::create('string')
        ->setLabel(new TranslatableMarkup('Description'))
        ->setRequired(FALSE),
    ] + parent::propertyDefinitions($field_definition);
  }

}
