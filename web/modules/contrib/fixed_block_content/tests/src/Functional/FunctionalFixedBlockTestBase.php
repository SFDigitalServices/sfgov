<?php

namespace Drupal\Tests\fixed_block_content\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for functional tests for the fixed block content.
 */
abstract class FunctionalFixedBlockTestBase extends BrowserTestBase {

  /**
   * The default theme for browser tests.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block_content',
    'hal',
    'serialization',
    'fixed_block_content',
  ];

  /**
   * The fixed block to work with.
   *
   * @var \Drupal\fixed_block_content\FixedBlockContentInterface
   */
  protected $fixedBlock;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a content block type.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $type = $entity_type_manager->getStorage('block_content_type')
      ->create([
        'id' => 'basic',
        'label' => 'Basic',
        'revision' => FALSE,
      ]);
    $type->save();
    // Add body field.
    block_content_add_body_field($type->id());

    // Create a fixed content block.
    $this->fixedBlock = $entity_type_manager->getStorage('fixed_block_content')
      ->create([
        'id' => 'basic_fixed',
        'title' => 'Basic fixed',
        'block_content_bundle' => 'basic',
      ]);
    $this->fixedBlock->save();

    // Place the fixed block.
    $this->drupalPlaceBlock('fixed_block_content:basic_fixed');
  }

}
