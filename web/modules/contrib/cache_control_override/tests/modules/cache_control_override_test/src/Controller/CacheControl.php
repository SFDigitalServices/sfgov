<?php

namespace Drupal\cache_control_override_test\Controller;

use Drupal\Core\Cache\Cache;

/**
 * Controllers for testing the cache control override.
 */
class CacheControl {

  /**
   * Controller callback: Test content with a specified max age.
   *
   * @param int $max_age
   *   Max age value to be used in the response.
   *
   * @return array
   *   Render array of page output.
   */
  public function maxAge($max_age = Cache::PERMANENT) {

    return [
      '#markup' => 'Max age test content',
      '#cache' => ['max-age' => $max_age],
    ];
  }

}
