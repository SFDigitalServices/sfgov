<?php

namespace Drupal\scheduler_content_moderation_integration\EventSubscriber;

use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handle scheduler events.
 *
 * The six possible Scheduler events are:
 * SchedulerEvents::PRE_PUBLISH
 * SchedulerEvents::PUBLISH
 * SchedulerEvents::PRE_UNPUBLISH
 * SchedulerEvents::UNPUBLISH
 * SchedulerEvents::PRE_PUBLISH_IMMEDIATELY
 * SchedulerEvents::PUBLISH_IMMEDIATELY.
 */
class SchedulerEventSubscriber implements EventSubscriberInterface {

  /**
   * Operations to perform after Scheduler publishes a node immediately.
   *
   * This is during the edit process, not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The event being acted on.
   */
  public function publishImmediately(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    $node->set('moderation_state', $node->publish_state->getValue());

    $event->setNode($node);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The values in the arrays give the function names above.
    $events[SchedulerEvents::PUBLISH_IMMEDIATELY][] = ['publishImmediately'];
    return $events;
  }

}
