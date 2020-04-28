<?php

namespace Drupal\simple_sitemap\Queue;

use Drupal\Core\Queue\DatabaseQueue;

/**
 * Class SimplesitemapQueue
 * @package Drupal\simple_sitemap\Queue
 */
class SimplesitemapQueue extends DatabaseQueue {

  /**
   * Overrides \Drupal\Core\Queue\DatabaseQueue::claimItem().
   *
   * Unlike \Drupal\Core\Queue\DatabaseQueue::claimItem(), this method provides
   * a default lease time of 0 (no expiration) instead of 30. This allows the
   * item to be claimed repeatedly until it is deleted.
   */
  public function claimItem($lease_time = 0) {
    try {
      $item = $this->connection->queryRange('SELECT data, item_id FROM {queue} q WHERE name = :name ORDER BY item_id ASC', 0, 1, [':name' => $this->name])->fetchObject();
      if ($item) {
        $item->data = unserialize($item->data);
        return $item;
      }
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }

    return FALSE;
  }

  public function createItems($data_sets) {
    $try_again = FALSE;
    try {
      $id = $this->doCreateItems($data_sets);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if (!$try_again = $this->ensureTableExists()) {
        // If the exception happened for other reason than the missing table,
        // propagate the exception.
        throw $e;
      }
    }
    // Now that the table has been created, try again if necessary.
    if ($try_again) {
      $id = $this->doCreateItems($data_sets);
    }

    return $id;
  }

  protected function doCreateItems($data_sets) {
    $query = $this->connection->insert(static::TABLE_NAME)
      ->fields(['name', 'data', 'created']);

    foreach ($data_sets as $i => $data) {
      $query->values([
        $this->name,
        serialize($data),
        time(),
      ]);
    }

    return $query->execute();
  }

  public function deleteItems($item_ids) {
    try {
      $this->connection->delete(static::TABLE_NAME)
        ->condition('item_id', $item_ids, 'IN')
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

}
