<?php

namespace Drupal\tmgmt\Events;

use Drupal\tmgmt\JobInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Represents a job item about to be added to a continuous job.
 *
 * @see \Drupal\tmgmt\ContinuousSourceInterface::shouldCreateContinuousItem()
 */
class ShouldCreateJobEvent extends Event {

  /**
   *  Continuous job entity.
   *
   * @var \Drupal\tmgmt\JobInterface
   */
  protected $job;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The item type.
   *
   * @var string
   */
  protected $itemType;

  /**
   * The source item id.
   *
   * @var string
   */
  protected $itemId;

  /**
   * Whether or not the job should be created.
   *
   * @var bool
   */
  protected $shouldCreateItem;

  /**
   * ShouldCreateJobEvent constructor.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   Continuous job.
   * @param string $plugin
   *   The plugin name.
   * @param string $item_type
   *   The source item type.
   * @param string $item_id
   *   The source item id.
   * @param bool $should_create_item
   *   Whether or not the item should be created.
   */
  public function __construct(JobInterface $job, $plugin, $item_type, $item_id, $should_create_item) {
    $this->job = $job;
    $this->plugin = $plugin;
    $this->itemType = $item_type;
    $this->itemId = $item_id;
    $this->shouldCreateItem = $should_create_item;
  }

  /**
   * Gets the job entity.
   *
   * @return \Drupal\tmgmt\JobInterface
   *   The Job object.
   */
  public function getJob() {
    return $this->job;
  }

  /**
   * Gets the plugin ID.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPlugin() {
    return $this->plugin;
  }

  /**
   * Gets the item type.
   *
   * @return string
   *   The item type.
   */
  public function getItemType() {
    return $this->itemType;
  }

  /**
   * Gets the item id.
   *
   * @return string
   *   The item id.
   */
  public function getItemId() {
    return $this->itemId;
  }

  /**
   * Returns whether the job item should be created.
   *
   * @return bool
   *   Whether or not the job item should be created.
   */
  public function shouldCreateItem() {
    return $this->shouldCreateItem;
  }

  /**
   * Sets whether or not the job item should be created.
   *
   * @param bool $should_create_item
   *   TRUE if the job item should be created, FALSE if not.
   */
  public function setShouldCreateItem($should_create_item) {
    $this->shouldCreateItem = $should_create_item;
  }

}
