<?php

namespace Drupal\fixed_block_content\EventSubscriber;

use Drupal\block_content\BlockContentEvents;
use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Event\BlockContentGetDependencyEvent;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber that sets the access dependency for fixed blocks.
 *
 * @see \Drupal\file\FileAccessControlHandler::checkAccess()
 * @see \Drupal\block_content\BlockContentAccessControlHandler::checkAccess()
 */
class SetFixedBlockDependency implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The primary database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs SetFixedBlockDependency object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The primary database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      BlockContentEvents::BLOCK_CONTENT_GET_DEPENDENCY => 'onGetDependency',
    ];
  }

  /**
   * Handles the BlockContentEvents::INLINE_BLOCK_GET_DEPENDENCY event.
   *
   * @param \Drupal\block_content\Event\BlockContentGetDependencyEvent $event
   *   The event.
   */
  public function onGetDependency(BlockContentGetDependencyEvent $event) {
    if ($dependency = $this->getFixedBlockDependency($event->getBlockContentEntity())) {
      $event->setAccessDependency($dependency);
    }
  }

  /**
   * Gets the access dependent fixed block for a given custom block content.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The custom block.
   *
   * @return \Drupal\fixed_block_content\Entity\FixedBlockContent|null
   *   The fixed block content to which the block content belongs, NULL if none
   *   found.
   */
  protected function getFixedBlockDependency(BlockContentInterface $block_content) {
    // Search the fixed block of the edited custom block.
    // @todo move this to a service.
    $fbids = $this->database->select('fixed_block_content', 'fbc')
      ->fields('fbc', ['fbid'])
      ->range(0, 1)
      ->condition('fbc.bid', $block_content->id())
      ->execute();

    if ($fbid = $fbids->fetchField()) {
      return $this->entityTypeManager
        ->getStorage('fixed_block_content')->load($fbid);
    }

    return NULL;
  }

}
