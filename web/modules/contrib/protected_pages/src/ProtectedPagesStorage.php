<?php

/**
 * @file
 * Contains \Drupal\protected_pages\ProtectedPagesStorage.
 */

namespace Drupal\protected_pages;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;

/**
 * Defines the protected page storage service.
 */
class ProtectedPagesStorage {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new protected page storage service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Insert data into protected pages table.
   *
   * @param array $page_data
   *   An array containing all values.
   *
   * @return int $pid
   *   The protected page id.
   */
  public function insertProtectedPage(array $page_data) {
    $query = $this->connection->insert('protected_pages')
        ->fields(array('password', 'path'))
        ->values($page_data);
    $pid = $query->execute();
    return $pid;
  }

  /**
   * Updates data into protected pages table.
   *
   * @param array $page_data
   *   An array containing all values.
   * @param int $pid
   *   The protected page id.
   */
  public function updateProtectedPage(array $page_data, $pid) {
    $this->connection->update('protected_pages')
        ->fields($page_data)
        ->condition('pid', $pid)
        ->execute();
  }

  /**
   * Delete protected page from database.
   *
   * @param int $pid
   *   The protected page id.
   */
  public function deleteProtectedPage($pid) {
    $this->connection->delete('protected_pages')
        ->condition('pid', $pid)
        ->execute();
  }

  /**
   * Fetches protected page records from database.
   *
   * @param array $fields
   *   An array containing all fields.
   * @param array $query_conditions
   *   An array containing all conditions.
   * @param bool $get_single_field
   *   Boolean to check if functions needs to return one or multiple fields.
   */
  public function loadProtectedPage($fields = array(), $query_conditions = array(), $get_single_field = FALSE) {
    $select = $this->connection->select('protected_pages');
    if (count($fields)) {
      $select->fields('protected_pages', $fields);
    }
    else {
      $select->fields('protected_pages');
    }

    if (count($query_conditions)) {
      if (isset($query_conditions['or']) && count($query_conditions['or'])) {
        $conditions = new Condition('OR');
        foreach ($query_conditions['or'] as $condition) {
          $conditions->condition($condition['field'], $condition['value'], $condition['operator']);
        }
        $select->condition($conditions);
      }
      if (isset($query_conditions['and']) && count($query_conditions['and'])) {

        foreach ($query_conditions['and'] as $condition) {
          $select->condition($condition['field'], $condition['value'], $condition['operator']);
        }
      }
      if (isset($query_conditions['general']) && count($query_conditions['general'])) {

        foreach ($query_conditions['general'] as $condition) {
          $select->condition($condition['field'], $condition['value'], $condition['operator']);
        }
      }
    }

    if ($get_single_field) {
      $select->range(0, 1);
      $result = $select->execute()->fetchField();
    }
    else {
      $result = $select->execute()->fetchAll();
    }

    return $result;
  }

  /**
   * Fetches all protected pages records from database.
   */
  public function loadAllProtectedPages() {
    $results = $this->connection->select('protected_pages', 'p')
        ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->fields('p')
        ->orderBy('p.pid', 'DESC')
        ->limit(20)
        ->execute()
        ->fetchAll();

    return $results;
  }

}
