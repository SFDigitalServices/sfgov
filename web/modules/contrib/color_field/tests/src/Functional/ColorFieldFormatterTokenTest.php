<?php

namespace Drupal\Tests\color_field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests color field formatters.
 *
 * @group color_field
 */
class ColorFieldFormatterTokenTest extends ColorFieldFunctionalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'color_field',
    'token',
  ];

  /**
   * Test color_field_formatter_css formatter.
   */
  public function testTokens() {
    $this->form->setComponent('field_color', [
      'type' => 'color_field_widget_default',
    ])->save();

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_color[0][color]' => "#9C59D1",
      'field_color[0][opacity]' => 0.95,
    ];
    $this->display->setComponent('field_color', [
      'type' => 'color_field_formatter_css',
      'weight' => 1,
      'settings' => [
        'selector' => '.node-[node:content-type]',
        'property' => 'background-color',
        'important' => FALSE,
        'opacity' => TRUE,
      ],
      'label' => 'hidden',
    ])->save();

    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertSession()->responseContains('.node-article { background-color: RGBA(156,89,209,0.95); }');

    // Ensure 2 fields on the same entity are both rendered properly.
    FieldStorageConfig::create([
      'field_name' => 'field_text_color',
      'entity_type' => 'node',
      'type' => 'color_field_type',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_text_color',
      'label' => 'Text Color',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    $this->display->setComponent('field_text_color', [
      'type' => 'color_field_formatter_css',
      'weight' => 1,
      'settings' => [
        'selector' => '.node-[node:content-type]',
        'property' => 'color',
        'important' => FALSE,
        'opacity' => TRUE,
      ],
      'label' => 'hidden',
    ])->save();
    $this->form->setComponent('field_text_color', [
      'type' => 'color_field_widget_default',
    ])->save();

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_color[0][color]' => "#000000",
      'field_color[0][opacity]' => 0.1,
      'field_text_color[0][color]' => "#000000",
      'field_text_color[0][opacity]' => 1,
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertSession()->responseContains('.node-article { background-color: RGBA(0,0,0,0.1); }');
    $this->assertSession()->responseContains('.node-article { color: RGBA(0,0,0,1); }');
  }

}
