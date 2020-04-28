<?php

use Drupal\Component\Serialization\Json;

/**
 * Converts job item data storage to JSON.
 */
function tmgmt_post_update_json(&$sandbox = NULL) {
  $job_item_storage = \Drupal::entityTypeManager()->getStorage('tmgmt_job_item');
  if (!isset($sandbox['current_count'])) {
    $query = $job_item_storage->getQuery();
    $sandbox['total_count'] = $query->count()->execute();
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $query = $job_item_storage->getQuery();
  $query->range($sandbox['current_count'], 25);
  $query->sort('tjiid');
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }

  /** @var \Drupal\tmgmt\Entity\JobItem[] $job_items */
  $job_items = $job_item_storage->loadMultiple($result);
  foreach ($job_items as $job_item) {

    // Check if there is data and that it is a serialized array, convert it.
    if ($job_item->get('data')->value && mb_substr($job_item->get('data')->value, 0, 1) == 'a') {
      $data = unserialize($job_item->get('data')->value);
      $job_item->set('data', Json::encode($data));
      $job_item->save();
    }

  }

  $sandbox['current_count'] += 25;
  if ($sandbox['current_count'] >= $sandbox['total_count']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['total_count'] - $sandbox['current_count']) / $sandbox['total_count'];
  }
}
