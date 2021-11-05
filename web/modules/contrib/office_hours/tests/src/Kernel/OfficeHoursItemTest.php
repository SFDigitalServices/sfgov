<?php

namespace Drupal\Tests\office_hours\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Class that tests OfficeHoursField.
 *
 * @package Drupal\Tests\office_hours\Kernel
 *
 * @group office_hours
 */
class OfficeHoursItemTest extends FieldKernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['office_hours'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_office_hours',
      'type' => 'office_hours',
      'entity_type' => 'entity_test',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'element_type' => 'office_hours_datelist',
      ],
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => [
        // @todo Test all settings.
        'cardinality_per_day' => 2,
        // 'time_format' => 'G',
        // 'increment' => 30,
        // 'comment' => 2,
        // 'valhrs' => false,
        // 'required_start' => false,
        // 'required_end' => false,
        // 'limit_start' => '',
        // 'limit_end' => '',
      ],
      'default_value' => [
        [
          'day' => 0,
          'starthours' => 900,
          'endhours' => 1730,
          'comment' => 'Test comment',
        ],
        [
          'day' => 1,
          'starthours' => 700,
          'endhours' => 1800,
          'comment' => 'Test comment',
        ],
      ],
    ]);
    $this->field->save();

    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $entity_display */
    $entity_display = EntityViewDisplay::create([
      'targetEntityType' => $this->field->getTargetEntityTypeId(),
      'bundle' => $this->field->getTargetBundle(),
      'mode' => 'default',
    ]);
    // Save the office hours field to check if the config schema is valid.
    // @todo D9 test
    // Table formatter.
    $entity_display->setComponent('field_office_hours', ['type' => 'office_hours_table']);
    $entity_display->save();
    // Default formatter.
    $entity_display->setComponent('field_office_hours', ['type' => 'office_hours']);
    $entity_display->save();
  }

  /**
   * Tests the Office Hours field can be added to an entity type.
   */
  public function testOfficeHoursField() {
    $this->fieldStorage->setSetting('element_type', 'office_hours_datelist');
    $this->fieldStorage->save();

    // Verify entity creation.
    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create();
    $field_name = 'field_office_hours';
    $value = [
      [
        'day' => '2',
        'starthours' => '1330',
        'endhours' => '2000',
        'comment' => ''],
      [
        'day' => '3',
        'starthours' => '900',
        'endhours' => '2000',
        'comment' => ''],
    ];
    $entity->set($field_name, $value);
    $entity->setName($this->randomMachineName());
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->get($field_name));
    $this->assertInstanceOf(FieldItemInterface::class, $entity->get($field_name)->first());

    // Verify changing the field value.
    $new_value = [
      [
        'day' => '2',
        'starthours' => '1430',
        'endhours' => '2000',
        'comment' => ''],
      [
        'day' => '3',
        'starthours' => '1900',
        'endhours' => '2000',
        'comment' => ''],
    ];
    // $entity->$field_name->value = $new_value;
    $entity->$field_name->setValue($new_value);
    $test_value = $entity->$field_name->first()->getValue();

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = EntityTest::load($id);
    $test_value = $entity->$field_name->first()->getValue();
    $this->assertEquals(implode('/',$new_value[0]), implode('/',$test_value));

    // Test the generateSampleValue() method.
    $entity = EntityTest::create();
    $entity->$field_name->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
