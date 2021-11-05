<?php

namespace Drupal\cache_control_override\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Cache Control Override.
 */
class CacheControlOverrideSubscriber implements EventSubscriberInterface {

  /**
   * Overrides cache control header if any of override methods are enabled.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();

    // If the current response isn't an implementation of the
    // CacheableResponseInterface, then there is nothing we can override.
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    // If FinishResponseSubscriber didn't set the response as cacheable, then
    // don't override anything.
    if (!$response->headers->hasCacheControlDirective('max-age') || !$response->headers->hasCacheControlDirective('public')) {
      return;
    }

    $max_age = $response->getCacheableMetadata()->getCacheMaxAge();

    // We treat permanent cache max-age as default therefore we don't override
    // the max-age.
    if ($max_age != Cache::PERMANENT) {
      $response->headers->set('Cache-Control', 'public, max-age=' . $max_age);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
