<?php

use Drupal\Component\Serialization\Json;

/**
 * Converts task item data storage to JSON.
 */
function tmgmt_local_post_update_json(&$sandbox = NULL) {
  $task_item_storage = \Drupal::entityTypeManager()->getStorage('tmgmt_local_task_item');
  if (!isset($sandbox['current_count'])) {
    $query = $task_item_storage->getQuery();
    $sandbox['total_count'] = $query->count()->execute();
    $sandbox['current_count'] = 0;

    if (empty($sandbox['total_count'])) {
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $query = $task_item_storage->getQuery();
  $query->range($sandbox['current_count'], 25);
  $query->sort('tltiid');
  $result = $query->execute();
  if (empty($result)) {
    $sandbox['#finished'] = 1;
    return;
  }

  /** @var \Drupal\tmgmt_local\Entity\LocalTaskItem[] $task_items */
  $task_items = $task_item_storage->loadMultiple($result);
  foreach ($task_items as $task_item) {

    // Check if there is data and that it is a serialized array, convert it.
    if ($task_item->get('data')->value && mb_substr($task_item->get('data')->value, 0, 1) == 'a') {
      $data = unserialize($task_item->get('data')->value);
      $task_item->set('data', Json::encode($data));
      $task_item->save();
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
