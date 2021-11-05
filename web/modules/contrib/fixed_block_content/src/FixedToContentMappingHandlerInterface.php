<?php

namespace Drupal\fixed_block_content;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\EntityHandlerInterface;

/**
 * Fixed block to block content mapping entity handler.
 *
 * Handler to manage the link between the fixed block and the block content.
 */
interface FixedToContentMappingHandlerInterface extends EntityHandlerInterface {

  /**
   * Links a fixed block to a block content.
   *
   * Existing block content will be released if present.
   *
   * @param \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block
   *   The fixed block.
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The block content.
   */
  public function setBlockContent(FixedBlockContentInterface $fixed_block, BlockContentInterface $block_content);

  /**
   * Gets the block content linked with a fixed block.
   *
   * @param string $fixed_block_id
   *   The ID of the fixed block.
   *
   * @return \Drupal\block_content\BlockContentInterface|null
   *   The block content, NULL if none found.
   */
  public function getBlockContent($fixed_block_id);

  /**
   * Breaks the link between a fixed block and a block content.
   *
   * @param \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block
   *   The fixed block whose block content to be released.
   */
  public function releaseBlockContent(FixedBlockContentInterface $fixed_block);

  /**
   * Gets the fixed block linked to the given block content.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The block content.
   *
   * @return \Drupal\fixed_block_content\FixedBlockContentInterface|null
   *   The fixed block, NULL if none found.
   */
  public function getFixedBlock(BlockContentInterface $block_content);

}
