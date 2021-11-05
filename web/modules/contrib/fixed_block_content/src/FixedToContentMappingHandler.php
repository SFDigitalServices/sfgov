<?php

namespace Drupal\fixed_block_content;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fixed block to block content mapping entity handler.
 *
 * @see \Drupal\fixed_block_content\FixedToContentMappingHandlerInterface
 */
class FixedToContentMappingHandler implements FixedToContentMappingHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The memory cache.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected $memoryCache;

  /**
   * MappingHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, MemoryCacheInterface $memory_cache) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->memoryCache = $memory_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('entity.memory_cache')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setBlockContent(FixedBlockContentInterface $fixed_block, BlockContentInterface $block_content) {
    // Bundle validation.
    if ($fixed_block->getBlockContentBundle() != $block_content->bundle()) {
      throw new \InvalidArgumentException(sprintf('The type of the given block "%s" does not match the configured block type "%s".', $block_content->bundle(), $fixed_block->getBlockContentBundle()));
    }

    if ($block_content->isNew()) {
      // Save the new block to get an ID, it is required to mapping.
      $block_content->save();
      // New blocks (not read from the storage) must be added to the entity
      // memory cache to maintain the same object across the execution.
      $cid = 'values:block_content:' . $block_content->id();
      $this->memoryCache->set($cid, $block_content);
    }

    if ($current_block = $this->getBlockContent($fixed_block->id())) {
      // If linking the same block, no action needed.
      if ($current_block->id() == $block_content->id()) {
        return;
      }

      // Replacing existing block content with another, we need first to
      // release the old one.
      $this->releaseBlockContent($fixed_block);
    }

    // Add fixed to content record in the mapping DB.
    $this->database->insert('fixed_block_content')
      ->fields([
        'fbid' => $fixed_block->id(),
        'bid' => $block_content->id(),
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockContent($fixed_block_id) {
    /** @var \Drupal\Core\Database\StatementInterface $bids */
    $bids = $this->database->select('fixed_block_content', 'fbc')
      ->fields('fbc', ['bid'])
      ->range(0, 1)
      ->condition('fbc.fbid', $fixed_block_id)
      ->execute();

    if ($bid = $bids->fetchField()) {
      $block_content_storage = $this->entityTypeManager->getStorage('block_content');
      return $block_content_storage->load($bid);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function releaseBlockContent(FixedBlockContentInterface $fixed_block) {
    // In the case of protected blocks, the block content must be deleted to
    // prevent orphaned block content.
    if ($fixed_block->isProtected()
      && $block_content = $fixed_block->getBlockContent(FALSE)) {
      $block_content->delete();
    }

    // Delete the fixed block link with the block content.
    $this->database->delete('fixed_block_content')
      ->condition('fbid', $fixed_block->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getFixedBlock(BlockContentInterface $block_content) {
    // New block content cannot be linked to any fixed block.
    if ($block_content->isNew()) {
      return NULL;
    }

    /** @var \Drupal\Core\Database\StatementInterface $bids */
    $bids = $this->database->select('fixed_block_content', 'fbc')
      ->fields('fbc', ['fbid'])
      ->range(0, 1)
      ->condition('fbc.bid', $block_content->id())
      ->execute();

    if ($fbid = $bids->fetchField()) {
      $fixed_block_content_storage = $this->entityTypeManager->getStorage('fixed_block_content');
      return $fixed_block_content_storage->load($fbid);
    }

    return NULL;
  }

}
