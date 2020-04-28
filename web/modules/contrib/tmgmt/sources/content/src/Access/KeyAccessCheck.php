<?php

namespace Drupal\tmgmt_content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Site\Settings;
use Drupal\tmgmt\JobItemInterface;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks access for TMGMT job item.
 */
class KeyAccessCheck implements RoutingAccessInterface {

  /**
   * Checks access for TMGMT job item.
   *
   * Checks access for TMGMT job item by comparing the hashed key from job item
   * data and key from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   * @param \Drupal\tmgmt\JobItemInterface $tmgmt_job_item
   *   Job item.
   *
   * @return AccessResult
   *   Returns TRUE if right key is in the request.
   */
  public function access(Request $request, JobItemInterface $tmgmt_job_item) {
    $key_from_request = $request->query->get('key');
    $result = AccessResult::forbidden();
    if ($key_from_request) {
      if ($key_from_request === $this->getKey($tmgmt_job_item) && $tmgmt_job_item->getJob()->isActive() && !($tmgmt_job_item->isAborted() || $tmgmt_job_item->isAccepted())) {
        $result = AccessResult::allowed();
      }
    }
    return $result->setCacheMaxAge(0);
  }

  /**
   * Generates a key from job item data that can be used in the URL.
   *
   * @param \Drupal\tmgmt\JobItemInterface $tmgmt_job_item
   *   Job item.
   *
   * @return string
   *   Returns hashed key that is safe to use in the URL.
   */
  public function getKey(JobItemInterface $tmgmt_job_item) {
    return Crypt::hmacBase64($tmgmt_job_item->id(), Settings::getHashSalt());
  }

}
