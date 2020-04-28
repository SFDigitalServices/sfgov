<?php

namespace Drupal\tmgmt_test;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt\JobItemInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test controller.
 */
class TestController extends ControllerBase {

  /**
   * Callback to add given job item into the cart.
   */
  function addToCart(JobItemInterface $tmgmt_job_item) {
    tmgmt_cart_get()->addExistingJobItems(array($tmgmt_job_item));
    return new Response('', 204);
  }
}
