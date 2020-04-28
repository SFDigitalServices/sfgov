<?php

namespace Drupal\fixed_block_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\fixed_block_content\FixedBlockContentInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Configuration entity for the fixed block content.
 *
 * @ConfigEntityType(
 *   id = "fixed_block_content",
 *   label = @Translation("Fixed block content"),
 *   config_prefix = "fixed_block_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   handlers = {
 *     "access" = "Drupal\fixed_block_content\FixedBlockContentAccessControlHandler",
 *     "list_builder" = "Drupal\fixed_block_content\FixedBlockContentListBuilder",
 *     "content_handler" = "Drupal\fixed_block_content\DefaultContentHandler",
 *     "form" = {
 *       "add" = "Drupal\fixed_block_content\Form\FixedBlockContentForm",
 *       "edit" = "Drupal\fixed_block_content\Form\FixedBlockContentForm",
 *       "delete" = "Drupal\fixed_block_content\Form\FixedBlockContentDeleteForm",
 *       "export" = "Drupal\fixed_block_content\Form\ExportConfirmForm",
 *       "import" = "Drupal\fixed_block_content\Form\ImportConfirmForm"
 *     }
 *   },
 *   links = {
 *     "collection" = "/admin/structure/block/block-content/fixed-block-content",
 *     "canonical" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}",
 *     "edit-form" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}/edit",
 *     "delete-form" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}/delete",
 *     "export-form" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}/export",
 *     "import-form" = "/admin/structure/block/block-content/fixed-block-content/manage/{fixed_block_content}/import"
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "block_content_bundle",
 *     "default_content"
 *   }
 * )
 */
class FixedBlockContent extends ConfigEntityBase implements FixedBlockContentInterface {

  /**
   * The block ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The block title.
   *
   * @var string
   */
  protected $title;

  /**
   * The block content bundle.
   *
   * @var string
   */
  protected $block_content_bundle;

  /**
   * The serialized default content for this fixed block.
   *
   * @var string
   */
  protected $default_content;

  /**
   * The current block content linked to this fixed block.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $blockContent;

  /**
   * {@inheritdoc}
   */
  public function getBlockContent($create = TRUE) {
    if ($this->blockContent) {
      return $this->blockContent;
    }

    $block_content_storage = $this->entityTypeManager()->getStorage('block_content');
    /** @var \Drupal\Core\Database\StatementInterface $bids */
    $bids = \Drupal::database()->select('fixed_block_content', 'fbc')
      ->fields('fbc', ['bid'])
      ->range(0, 1)
      ->condition('fbc.fbid', $this->id)
      ->execute();

    // Block content not found. Create one.
    if ($bid = $bids->fetchField()) {
      $this->blockContent = $block_content_storage->load($bid);
    }

    if (!$this->blockContent && $create) {
      if (!empty($this->default_content)) {
        $this->exportDefaultContent();
      }
      else {
        $this->setBlockContent();
      }
    }

    return $this->blockContent;
  }

  /**
   * Links a block content with this fixed block.
   *
   * The existent block content is not deleted. If the given block is new,
   * it is saved to reference it.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The new block content. If NULL a new empty block is created.
   *
   * @throws \InvalidArgumentException
   *   When the content type of the given block mismatches the configured type.
   */
  protected function setBlockContent(BlockContentInterface $block_content = NULL) {
    // Create a new empty block content if no one given.
    if (!$block_content) {
      /** @var \Drupal\block_content\BlockContentInterface $block_content */
      $block_content = $this->entityTypeManager()->getStorage('block_content')->create([
        'type' => $this->block_content_bundle,
        'info' => $this->title,
        'langcode' => $this->languageManager()->getDefaultLanguage()->getId(),
      ]);
      $block_content->enforceIsNew(TRUE);
      $block_content->setNewRevision(FALSE);
    }
    else {
      if ($block_content->bundle() != $this->block_content_bundle) {
        throw new \InvalidArgumentException(sprintf('The type of the given block "%s" does not match the configured block type "%s".', $block_content->bundle(), $this->block_content_bundle));
      }
    }

    // The custom block ID is needed to reference it.
    if ($block_content->isNew()) {
      $block_content->save();
    }

    // Link this fixed block with the content block.
    $this->blockContent = $block_content;
    \Drupal::database()->upsert('fixed_block_content')
      ->key('fbid')
      ->fields([
        'fbid' => $this->id,
        'bid' => $block_content->id(),
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockContentBundle() {
    return $this->block_content_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function exportDefaultContent() {
    $new_block_content = $this->entityTypeManager()->getHandler($this->getEntityTypeId(), 'content_handler')->exportDefaultContent($this);

    // Delete the current block content.
    if ($curent_block = $this->getBlockContent(FALSE)) {
      $curent_block->delete();
    }

    $this->setBlockContent($new_block_content);
  }

  /**
   * {@inheritdoc}
   */
  public function importDefaultContent() {
    $this->entityTypeManager()->getHandler($this->getEntityTypeId(), 'content_handler')->importDefaultContent($this);
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Add dependency on the linked block content.
    if ($block_content = $this->getBlockContent(FALSE)) {
      $this->addDependency($block_content->getConfigDependencyKey(), $block_content->getConfigDependencyName());
    }

    // Add dependency on the configured block content type.
    $block_content_type = $this->entityTypeManager()->getStorage('block_content_type')->load($this->block_content_bundle);
    $this->addDependency($block_content_type->getConfigDependencyKey(), $block_content_type->getConfigDependencyName());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Remove any fixed block links.
    \Drupal::database()->delete('fixed_block_content')
      ->condition('fbid', $this->id)
      ->execute();

    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    static::invalidateBlockPluginCache();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    static::invalidateBlockPluginCache();
  }

  /**
   * Invalidates the block plugin cache after changes and deletions.
   */
  protected static function invalidateBlockPluginCache() {
    // Invalidate the block cache to update custom block-based derivatives.
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

}
