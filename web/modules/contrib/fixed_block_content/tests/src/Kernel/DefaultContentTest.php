<?php

namespace Drupal\Tests\fixed_block_content\Kernel;

/**
 * Tests the fixed block default content management.
 *
 * @group fixed_block_content
 * @coversDefaultClass \Drupal\fixed_block_content\Entity\FixedBlockContent
 */
class DefaultContentTest extends FixedBlockContentKernelTestBase {

  /**
   * Tests the default content export.
   *
   * That is, copy contents from fixed block content configuration to its
   * regular block content.
   *
   * @covers ::exportDefaultContent
   */
  public function testDefaultContentExport() {
    // Test the block content creation on default content export. It must end
    // with the creation of a new (empty) block content even if trying to
    // update a non-existing linked block content.
    $this->fixedBlock->exportDefaultContent(TRUE);
    $block_content = $this->fixedBlock->getBlockContent(FALSE);
    $this->assertNotNull($block_content);
    // Test that it is a new block content.
    $this->assertEqual($block_content->id(), 1);

    // Tests that the default content export updates existing block content.
    $block_content->get('body')->setValue('To be overridden.');
    $block_content->save();
    $this->fixedBlock->exportDefaultContent(TRUE);
    $block_content = $this->fixedBlock->getBlockContent(FALSE);
    // Must be the same block content.
    $this->assertEqual($block_content->id(), 1);
    // The body must be empty, as it is in the default content.
    $this->assertTrue($block_content->get('body')->isEmpty());

    // Tests that the default content is stored persistent.
    $test_content = 'To be restored.';
    $block_content->get('body')->setValue($test_content);
    $this->fixedBlock->importDefaultContent();
    $this->fixedBlock->exportDefaultContent();
    $block_content = $this->fixedBlock->getBlockContent(FALSE);
    // It must be a new block content.
    $this->assertEqual($block_content->id(), 2);
    // Tests that the default content was correctly exported.
    $this->assertEqual($block_content->get('body')->getString(), $test_content);
  }

  /**
   * Tests the default content import.
   *
   * That is, copy the current block contents to the fixed block content
   * configuration.
   *
   * @covers ::importDefaultContent
   */
  public function testDefaultContentImport() {
    // Basic import default content test.
    $this->fixedBlock->importDefaultContent();
    $block_content = $this->fixedBlock->getBlockContent(FALSE);
    // Import in an empty fixed block will create a new block content.
    $this->assertNotNull($block_content);
    // The default_content property must has some value.
    $this->assertNotEmpty($this->fixedBlock->get('default_content'));

    // Tests default content update.
    $test_content = 'To be imported.';
    $block_content->get('body')->setValue($test_content);
    $block_content->save();
    $this->fixedBlock->importDefaultContent();
    $this->assertContains($test_content, $this->fixedBlock->get('default_content'));
  }

}
