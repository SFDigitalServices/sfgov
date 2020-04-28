<?php

namespace Drupal\Tests\color_field\Kernel;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the color field type.
 *
 * @group color_field
 */
class ColorFieldTypeTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['color_field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a color field storage and field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'color_field_type',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
    ])->save();

    // Create a second field with different options.
    FieldStorageConfig::create([
      'field_name' => 'field_hex',
      'entity_type' => 'entity_test',
      'type' => 'color_field_type',
      'settings' => [
        'format' => 'hexhex',
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_hex',
      'bundle' => 'entity_test',
      'settings' => [
        'opacity' => FALSE,
      ],
    ])->save();
  }

  /**
   * Tests using entity fields of the telephone field type.
   */
  public function testTestItem() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $color = '#5BCEFA';
    $lcolor = '5bcefa';
    $opacity = 0.5;
    $entity->field_test->color = $color;
    $entity->field_test->opacity = $opacity;
    $entity->field_hex->opacity = 5;
    $entity->field_hex->color = $color;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_test instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEquals($color, $entity->field_test->color);
    $this->assertEquals($opacity, $entity->field_test->opacity);
    $this->assertEquals($color, $entity->field_test[0]->color);

    // Verify field setting is respected.
    $this->assertEquals($lcolor, $entity->field_hex->color);
    $this->assertEquals('', $entity->field_hex->opacity);

    // Verify changing the field value.
    $new_value = '#FFFFFF';
    $entity->field_test->color = $new_value;
    $this->assertEquals($new_value, $entity->field_test->color);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEquals($new_value, $entity->field_test->color);

    // Verify setting the color with one format will save as the desired format.
    $new_value = 'F5A9B8';
    $entity->field_test->color = $new_value;
    $this->assertEquals($new_value, $entity->field_test->color);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
