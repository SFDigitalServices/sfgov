<?php

namespace Drupal\public_preview\Storage;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\public_preview\Model\Preview;
use Exception;
use PDO;

/**
 * Class PreviewStorage.
 *
 * Based on the Drupal\Core\Path\AliasStorage.
 *
 * @package Drupal\public_preview\Storage
 */
class PreviewStorage implements PreviewStorageInterface {

  /**
   * The table for the url_path storage.
   */
  const TABLE = 'public_preview';

  /**
   * The fetch mode used by setFetchMode.
   */
  const FETCH_MODE = PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE;

  /**
   * The fetch arguments used by setFetchMode.
   *
   * It has every Preview property listed, excluding 'original'.
   */
  const FETCH_ARGUMENTS = ['id', 'nid', 'hash', 'langcode'];

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a Preview CRUD object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing previews.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $values) {
    // Don't try to set the original, if it exists.
    if (isset($values['original'])) {
      unset($values['original']);
    }

    $result = FALSE;
    // If there's a value, update the preview.
    if (isset($values['id'])) {
      // Fetch the current values so that an update hook can identify what
      // exactly changed.
      try {
        $query = $this->connection->select(static::TABLE);
        $query->fields(static::TABLE, static::FETCH_ARGUMENTS);
        $query->condition('id', $values['id']);
        $result = $query->execute();
        $result->setFetchMode(static::FETCH_MODE, Preview::class, static::FETCH_ARGUMENTS);
        $original = $result->fetchObject();
      }
      catch (Exception $e) {
        $this->catchException($e);
        $original = NULL;
      }
      $query = $this->connection->update(static::TABLE)
        ->fields($values)
        ->condition('id', $values);
      $result = $query->execute();
      $values['original'] = $original;
    }
    // Otherwise, insert a new preview.
    else {
      $tryAgain = FALSE;
      try {
        $query = $this->connection->insert(static::TABLE)
          ->fields($values);
        $result = $query->execute();
      }
      catch (Exception $e) {
        // If there was an exception, try to create the table.
        if (!$tryAgain = $this->ensureTableExists()) {
          // If the exception happened for other reason than the missing table,
          // propagate the exception.
          throw $e;
        }
      }
      // Now that the table has been created, try again if necessary.
      if ($tryAgain) {
        $query = $this->connection->insert(static::TABLE)
          ->fields($values);
        $result = $query->execute();
      }

      $values['id'] = $result;
    }

    // Invalidate route_match cache and return a Preview object.
    if ($result) {
      Cache::invalidateTags(['route_match']);
      return Preview::create($values);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $conditions) {
    $select = $this->connection->select(static::TABLE);

    foreach ($conditions as $field => $value) {
      if ($field === 'hash') {
        // Use LIKE for case-insensitive matching.
        $select->condition($field, $this->connection->escapeLike($value), 'LIKE');
      }
      else {
        $select->condition($field, $value);
      }
    }

    try {
      $result = $select
        ->fields(static::TABLE, static::FETCH_ARGUMENTS)
        ->orderBy('id', 'DESC')
        ->range(0, 1)
        ->execute();

      if (NULL === $result) {
        return FALSE;
      }

      $result->setFetchMode(static::FETCH_MODE, Preview::class, static::FETCH_ARGUMENTS);
      return $result->fetch();
    }
    catch (Exception $e) {
      $this->catchException($e);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = []) {
    $select = $this->connection->select(static::TABLE);

    if (!empty($ids)) {
      $select->condition('id', $ids, 'in');
    }

    try {
      $result = $select->fields(static::TABLE)
        ->orderBy('id')
        ->execute();

      if (NULL === $result) {
        return FALSE;
      }

      $result->setFetchMode(static::FETCH_MODE, Preview::class, static::FETCH_ARGUMENTS);

      return $result->fetchAllAssoc('id');
    }
    catch (Exception $e) {
      $this->catchException($e);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadForNode($nid) {
    $select = $this->connection->select(static::TABLE);

    try {
      $select->fields(static::TABLE);
      $select->condition('nid', $nid);
      $result = $select->execute();

      if (NULL === $result) {
        return FALSE;
      }

      $result->setFetchMode(static::FETCH_MODE, Preview::class, static::FETCH_ARGUMENTS);

      return $result->fetchAllAssoc('langcode');
    }
    catch (Exception $e) {
      $this->catchException($e);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $conditions) {
    $query = $this->connection->delete(static::TABLE);
    foreach ($conditions as $field => $value) {
      if ($field === 'hash') {
        // Use LIKE for case-insensitive matching.
        $query->condition($field, $this->connection->escapeLike($value), 'LIKE');
      }
      else {
        $query->condition($field, $value);
      }
    }
    try {
      $deleted = $query->execute();
    }
    catch (Exception $e) {
      $this->catchException($e);
      $deleted = FALSE;
    }
    Cache::invalidateTags(['route_match']);
    return $deleted;
  }

  /**
   * Check if the table exists and create it if not.
   */
  protected function ensureTableExists() {
    try {
      $databaseSchema = $this->connection->schema();
      if (!$databaseSchema->tableExists(static::TABLE)) {
        $schemaDefinition = static::schemaDefinition();
        $databaseSchema->createTable(static::TABLE, $schemaDefinition);
        return TRUE;
      }
    }

    // If another process has already created the table,
    // attempting to recreate it will throw an exception.
    // In this case just catch the exception and do nothing.
    catch (SchemaObjectExistsException $e) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Act on an exception when url_alias might be stale.
   *
   * If the table does not yet exist, that's fine, but if the table exists and
   * yet the query failed, then the url_alias is stale and the exception needs
   * to propagate.
   *
   * @param \Exception $exception
   *   The exception.
   *
   * @throws \Exception
   */
  protected function catchException(Exception $exception) {
    if ($this->connection->schema()->tableExists(static::TABLE)) {
      throw $exception;
    }
  }

  /**
   * Defines the schema for the {public_preview} table.
   */
  public static function schemaDefinition() {
    return [
      'description' => 'The table for public node previews.',
      'fields' => [
        'id' => [
          'description' => 'The ID of the preview.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'nid' => [
          'description' => 'The ID of the node.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'hash' => [
          'description' => 'The public hash.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
        'langcode' => [
          'description' => 'The language code.',
          'type' => 'varchar_ascii',
          'length' => 12,
          'not null' => TRUE,
          'default' => '',
        ],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'hash_langcode_id' => ['hash', 'langcode', 'id'],
        'nid_langcode_id' => ['nid', 'langcode', 'id'],
      ],
      // Only for documentation.
      'foreign_keys' => [
        'node' => [
          'table' => 'node',
          'columns' => [
            'nid' => 'nid',
          ],
        ],
      ],
    ];
  }

}
