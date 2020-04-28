<?php

namespace Drupal\fixed_block_content;

use Drupal\Core\Entity\EntityHandlerInterface;

/**
 * Default content handler interface for the fixed block content entity type.
 */
interface DefaultContentHandlerInterface extends EntityHandlerInterface {

  /**
   * Export the default content stored in config to a new custom block.
   *
   * @param \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block
   *   The fixed block to work with.
   *
   * @return \Drupal\block_content\BlockContentInterface
   *   The new custom block content (unsaved), NULL if no default content or
   *   there ware errors.
   */
  public function exportDefaultContent(FixedBlockContentInterface $fixed_block);

  /**
   * Sets the current content of the custom block as the fixed default content.
   *
   * @param \Drupal\fixed_block_content\FixedBlockContentInterface $fixed_block
   *   The fixed block to work with.
   */
  public function importDefaultContent(FixedBlockContentInterface $fixed_block);

}
