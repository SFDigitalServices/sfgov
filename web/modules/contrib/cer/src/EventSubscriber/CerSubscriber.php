<?php

namespace Drupal\cer\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The Event Subscriber for Corresponding References.
 */
class CerSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    // TODO: Register event to update corresponding references when entity types are updated

    return $events;
  }
}
