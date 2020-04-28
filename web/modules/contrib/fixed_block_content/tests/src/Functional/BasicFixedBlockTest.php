<?php

namespace Drupal\Tests\fixed_block_content\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the basic fixed block content functionality.
 *
 * @group fixed_block_content
 */
class BasicFixedBlockTest extends BrowserTestBase {

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

  /**
   * Tests that the content block is created on view.
   */
  public function testContentBlockCreationOnView() {
    // Gets the home page.
    $this->drupalGet('<front>');

    // Check that the custom block was created.
    $content_blocks = \Drupal::entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties(['info' => $this->fixedBlock->label()]);
    $this->assertTrue($content_blocks, 'Automatic block content creation failed.');
  }

  /**
   * Tests the default content export.
   */
  public function testDefaultContent() {
    // Random content.
    $random_content = $this->randomString(128);

    // Gets the block content. An empty one will be created.
    $block_content = $this->fixedBlock->getBlockContent();

    // Sets the block content in the body field.
    $block_content->get('body')->setValue($random_content);

    // Import and save current block contents as the default content.
    $this->fixedBlock->importDefaultContent();
    $this->fixedBlock->save();

    // Delete the block content.
    $block_content->delete();

    // Gets the home page.
    $this->drupalGet('<front>');

    // Default content is expected.
    $this->assertSession()->pageTextContains($random_content);
  }

}
