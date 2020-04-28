<?php

namespace Drupal\tmgmt_test\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\tmgmt\Events\ShouldCreateJobEvent;
use Drupal\tmgmt\Events\ContinuousEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test event subscriber to test the continuous jobs events.
 */
class TestContinuousEventSubscriber implements EventSubscriberInterface {

  /**
   * Label that prevents a node from being added.
   */
  const DISALLOWED_LABEL = 'Testing SHOULD_CREATE_JOB event';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * TestContinuousEventSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Do not add the job if we have a filter match.
   *
   * @param \Drupal\tmgmt\Events\ShouldCreateJobEvent $event
   *   The event object.
   */
  public function onShouldCreateJob(ShouldCreateJobEvent $event) {
    // Filter out content.
    if ($event->getPlugin() === 'content') {
      $storage = $this->entityTypeManager->getStorage($event->getItemType());
      $entity = $storage->load($event->getItemId());
      if ($entity->label() === static::DISALLOWED_LABEL) {
        $event->setShouldCreateItem(FALSE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ContinuousEvents::SHOULD_CREATE_JOB][] = ['onShouldCreateJob'];
    return $events;
  }
}
