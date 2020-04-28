<?php

namespace Drupal\mandrill\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Sends queued mail messages.
 *
 * @QueueWorker(
 *   id = "mandrill_queue",
 *   title = @Translation("Sends queued mail messages"),
 *   cron = {"time" = 60}
 * )
 */
class MandrillQueueProcessor extends QueueWorkerBase {

  /**
   * Constructor.
   */
  public function __construct() {
    $config = \Drupal::service('config.factory')->get('mandrill.settings');
    $this->cron['time'] = $config->get('mandrill_queue_worker_timeout', 60);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /* @var $mandrill \Drupal\mandrill\MandrillService */
    $mandrill = \Drupal::service('mandrill.service');

    $mandrill->send($data['message']);
  }

}
