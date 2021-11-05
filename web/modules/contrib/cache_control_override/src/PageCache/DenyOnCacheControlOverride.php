<?php

namespace Drupal\cache_control_override\PageCache;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache policy for responses that have a bubbled max-age=0.
 */
class DenyOnCacheControlOverride implements ResponsePolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    if (!$response instanceof CacheableResponseInterface) {
      return NULL;
    }

    if ($response->getCacheableMetadata()->getCacheMaxAge() === 0) {
      // @TODO: This will affect users using Internal Page Cache as well, find a way to document that.
      return static::DENY;
    }
  }

}
