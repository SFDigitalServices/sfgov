<?php

namespace Drupal\Tests\fixed_block_content\Kernel;

use Drupal\Core\Database\Database;

/**
 * Tests the protected option of fixed blocks.
 *
 * @group fixed_block_content
 */
class ProtectedFixedBlockTest extends FixedBlockContentKernelTestBase {

  /**
   * Tests that a non-protected fixed block creates a reusable custom block.
   */
  public function testNonProtectedBlockCreation() {
    // Test that the custom block content from a non protected fixed block is
    // reusable.
    $block_content = $this->fixedBlock->getBlockContent();
    $this->assertEquals(TRUE, $block_content->isReusable());

    // Set the custom block to non-reusable.
    $block_content->setNonReusable();
    // Export it to config. The reusable field will be stored in the default
    // content.
    $this->fixedBlock->exportDefaultContent();
    // Delete it.
    $block_content->delete();
    // Import the default non-reusable block by the not protected fixed block.
    $block_content = $this->fixedBlock->getBlockContent();
    // The new block must be reusable despite the default content was exported
    // as non-reusable.
    $this->assertEquals(TRUE, $block_content->isReusable());
  }

  /**
   * Tests that a protected fixed block creates a non-reusable custom block.
   */
  public function testProtectedBlockCreation() {
    // Enables the protected option.
    $this->fixedBlock->setProtected();

    // Test that the custom block content from a non protected fixed block is
    // reusable.
    $block_content = $this->fixedBlock->getBlockContent();
    $this->assertEquals(FALSE, $block_content->isReusable());

    // Set the custom block to reusable.
    $block_content->setReusable();
    // Export it to config. The reusable field will be stored in the default
    // content.
    $this->fixedBlock->exportDefaultContent();
    // Delete it.
    $block_content->delete();
    // Import the default reusable block by the protected fixed block.
    $block_content = $this->fixedBlock->getBlockContent();
    // The new block must not be reusable despite the default content was
    // exported as reusable.
    $this->assertEquals(FALSE, $block_content->isReusable());

    // Test if the non-reusable custom block is deleted when the protected
    // fixed block too.
    $this->fixedBlock->delete();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $block_content = $entity_type_manager->getStorage('block_content')->load($block_content->id());
    $this->assertNull($block_content);
  }

  /**
   * Tests non-reusable block content deletion removes the link with its fixed.
   */
  public function testProtectedBlockDeletion() {
    // Enables the protected option.
    $this->fixedBlock->setProtected();
    // Get the block content, will create a new one.
    $block_content = $this->fixedBlock->getBlockContent();
    // Delete the non-reusable block content.
    $block_content->delete();

    // Check that the link with its fixed was removed.
    $connection = Database::getConnection();
    $count = $connection->select('fixed_block_content', 'fbc')
      ->condition('fbc.fbid', $this->fixedBlock->id())
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEmpty($count, 'Fixed block link with a deleted block content was found.');
  }

}
