<?php

namespace Drupal\Tests\fixed_block_content\Kernel;

/**
 * Tests the auto-create option of fixed blocks.
 *
 * @group fixed_block_content
 */
class AutoExportTest extends FixedBlockContentKernelTestBase {

  /**
   * The IDs of the test blocks.
   *
   * @var array
   */
  private $testBlocksId = [
    'auto_export_always',
    'auto_export_on_empty',
    'test_auto_export_disabled',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Export the initial config, including fixed blocks from the test module.
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Clean up default content in the test fixed blocks.
    $entity_storage = $this->entityTypeManager->getStorage('fixed_block_content');
    foreach ($entity_storage->loadMultiple($this->testBlocksId) as $fixed_block) {
      /** @var \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block */
      $fixed_block->set('default_content', '');
      $fixed_block->save();
    }
  }

  /**
   * Tests the automatic block content creation with no existing blocks.
   */
  public function testAutoExportOnEmpty() {
    // Perform a config import. This will re add the default content cleared in
    // the setup.
    $this->configImporter()->import();

    // Check that there is no block content for the fixed block with
    // the auto-export option disabled.
    /** @var \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block */
    $entity_storage = $this->entityTypeManager->getStorage('fixed_block_content');
    $fixed_block = $entity_storage->load('test_auto_export_disabled');
    $this->assertNull($fixed_block->getBlockContent(FALSE));

    // Check that the fixed block with the auto-export on empty option
    // has a block content linked.
    $fixed_block = $entity_storage->load('auto_export_on_empty');
    $block_content = $fixed_block->getBlockContent(FALSE);
    $this->assertNotNull($block_content);

    // Check that the fixed block with the auto-export always option
    // has a block content linked.
    $fixed_block = $entity_storage->load('auto_export_always');
    $block_content = $fixed_block->getBlockContent(FALSE);
    $this->assertNotNull($block_content);
  }

  /**
   * Tests the automatic block content creation on non empty.
   */
  public function testAutoExportOnNonEmpty() {
    // Check that there is no block content for the fixed block with
    // the auto-export option disabled.
    /** @var \Drupal\fixed_block_content\FixedBlockContentInterface[] $fixed_blocks */
    $fixed_blocks = [];
    /** @var \Drupal\block_content\BlockContentInterface[] $block_contents */
    $block_contents = [];
    $entity_storage = $this->entityTypeManager->getStorage('fixed_block_content');
    foreach ($this->testBlocksId as $block_id) {
      $fixed_blocks[$block_id] = $entity_storage->load($block_id);
      // Creates an empty block content for each test block.
      $block_contents[$block_id] = $fixed_blocks[$block_id]->getBlockContent();
    }

    // Do config import.
    $this->configImporter()->import();

    // Check that there is no changes in the block content linked to the test
    // fixed block with the auto-export option disabled.
    $this->assertEqual($fixed_blocks['test_auto_export_disabled']->getBlockContent(FALSE), $block_contents['test_auto_export_disabled']);

    // Check that the fixed block with the auto-export on empty option
    // has no changes.
    $this->assertEqual($fixed_blocks['auto_export_on_empty']->getBlockContent(FALSE), $block_contents['auto_export_on_empty']);

    // Check that the fixed block with the auto-export always option
    // has a block content linked.
    $this->assertNotEqual($fixed_blocks['auto_export_always']->getBlockContent(FALSE), $block_contents['auto_export_always']);
  }

}
