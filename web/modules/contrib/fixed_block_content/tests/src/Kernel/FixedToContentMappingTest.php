<?php

namespace Drupal\Tests\fixed_block_content\Kernel;

/**
 * Tests the fixed block content mapping handler.
 *
 * @group fixed_block_content
 */
class FixedToContentMappingTest extends FixedBlockContentKernelTestBase {

  /**
   * The fixed block content mapping handler.
   *
   * @var \Drupal\fixed_block_content\FixedToContentMappingHandlerInterface
   */
  protected $mappingHandler;

  /**
   * Block content to work with.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $blockContent;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mappingHandler = $this->entityTypeManager->getHandler('fixed_block_content', 'mapping_handler');
    // Create a simple block content to work with it.
    $this->blockContent = $this->entityTypeManager->getStorage('block_content')->create([
      'type' => 'basic',
      'info' => 'Basic block',
    ]);
  }

  /**
   * Tests the set block content method.
   */
  public function testSetBlockContent() {
    // Get the block content, it will be created.
    $this->mappingHandler->setBlockContent($this->fixedBlock, $this->blockContent);
    // Check that the fixed block has now the linked block content.
    $this->assertEquals($this->blockContent->id(), $this->fixedBlock->getBlockContent(FALSE)->id());
    // Setting the same block again should silently exit.
    $this->mappingHandler->setBlockContent($this->fixedBlock, $this->blockContent);
  }

  /**
   * Tests the set block content method in protected fixed blocks.
   */
  public function testSetBlockContentInProtected() {
    // Enable the protected mode.
    $this->fixedBlock->setProtected();
    // Get the block content. This will create a new block content.
    $block_content_id = $this->fixedBlock->getBlockContent()->id();
    // Replace existing block with a new one.
    $this->mappingHandler->setBlockContent($this->fixedBlock, $this->blockContent);
    // The previous block content must has been deleted.
    $this->assertEmpty($this->entityTypeManager->getStorage('block_content')->load($block_content_id));
  }

  /**
   * Tests the get block content method on not linked fixed block.
   */
  public function testGetBlockContentOnUnlinked() {
    // Try to get the block content for the initially unlinked fixed block.
    $block_content = $this->mappingHandler->getBlockContent($this->fixedBlock->id());
    $this->assertNull($block_content);

    // Get the existing block content (no create) should return NULL as well.
    $block_content = $this->fixedBlock->getBlockContent(FALSE);
    $this->assertNull($block_content);
  }

  /**
   * Tests that a new custom block is created if the existing was deleted.
   */
  public function testGetDeletedBlockContent() {
    // Get the block content, it will be created.
    $block_content = $this->fixedBlock->getBlockContent();
    // Preserve ID.
    $original_id = $block_content->id();
    // Delete it.
    $block_content->delete();
    // Get again the block content, a new one must be created.
    $block_content = $this->fixedBlock->getBlockContent();
    $this->assertNotEquals($block_content->id(), $original_id);
  }

  /**
   * Tests the block content release method.
   */
  public function testReleaseBlockContent() {
    // Release on not linked fixed should silently exit.
    $this->mappingHandler->releaseBlockContent($this->fixedBlock);

    // Get the block content. A new one will be created and linked.
    $block_id = $this->fixedBlock->getBlockContent()->id();
    // Release the block content.
    $this->mappingHandler->releaseBlockContent($this->fixedBlock);
    // The block content shouldn't be deleted.
    $this->assertNotNull($this->entityTypeManager->getStorage('block_content')->load($block_id));
    // The fixed block should be released.
    $this->assertEmpty($this->fixedBlock->getBlockContent(FALSE));

    // Enable de protected options.
    $this->fixedBlock->setProtected();
    // Get the block content. A new one will be created and linked.
    $block_id = $this->fixedBlock->getBlockContent()->id();
    // Release the block content.
    $this->mappingHandler->releaseBlockContent($this->fixedBlock);
    // The non-reusable block content should be deleted.
    $this->assertNull($this->entityTypeManager->getStorage('block_content')->load($block_id));
    // The fixed block should be released.
    $this->assertEmpty($this->fixedBlock->getBlockContent(FALSE));
  }

  /**
   * Tests the get fixed block method.
   */
  public function testGetFixedBlock() {
    // Try to get a fixed block for a new (not yet saved) block content.
    $this->assertEmpty($this->mappingHandler->getFixedBlock($this->blockContent));

    // Try to get a fixed block for a not linked block content.
    $this->blockContent->save();
    $this->assertEmpty($this->mappingHandler->getFixedBlock($this->blockContent));

    // Try to get a fixed block for a linked block content.
    $linked_block_content = $this->fixedBlock->getBlockContent();
    $this->assertEquals($this->mappingHandler->getFixedBlock($linked_block_content)->id(), $this->fixedBlock->id());
  }

}
