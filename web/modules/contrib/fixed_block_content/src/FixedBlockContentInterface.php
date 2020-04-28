<?php

namespace Drupal\fixed_block_content;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Fixed block content interface.
 */
interface FixedBlockContentInterface extends ConfigEntityInterface {

  /**
   * Returns the block content entity.
   *
   * @param bool $create
   *   Creates the block content if not found.
   *
   * @return \Drupal\block_content\BlockContentInterface
   *   The custom block linked to this fixed block content.
   */
  public function getBlockContent($create = TRUE);

  /**
   * Returns the block content bundle.
   *
   * @return string
   *   The block content bundle.
   */
  public function getBlockContentBundle();

  /**
   * Export the default content stored in config to the content block.
   *
   * The existent block content entity is deleted and replaced by a new one. If
   * no default content is set or is not valid, an empty block is created.
   */
  public function exportDefaultContent();

  /**
   * Import the current content block and set as the default content.
   */
  public function importDefaultContent();

}
