<?php

namespace Drupal\public_preview\Storage;

/**
 * Provides a class for CRUD operations on public previews.
 */
interface PreviewStorageInterface {

  /**
   * Create or update a preview.
   *
   * @param array $values
   *   An array of values to save.
   *
   * @return \Drupal\public_preview\Model\Preview|bool
   *   FALSE, if there was a handled exception.
   *   A Preview object otherwise.
   *
   * @throws \InvalidArgumentException
   * @throws \Exception
   */
  public function save(array $values);

  /**
   * Fetches a specific public preview from the database.
   *
   * The default implementation performs case-insensitive matching on the
   * '$hash' string.
   *
   * @param array $conditions
   *   An array of query conditions.
   *
   * @return \Drupal\public_preview\Model\Preview|bool
   *   FALSE, if there was a handled exception.
   *   A Preview object otherwise.
   *
   * @throws \Exception
   */
  public function load(array $conditions);

  /**
   * Load multiple previews.
   *
   * @param array $ids
   *   (optional) An array of IDs.
   *
   * @return \Drupal\public_preview\Model\Preview[]|bool
   *   FALSE, if there was a handled exception.
   *   An array of Preview objects or an empty array.
   *
   * @throws \Exception
   */
  public function loadMultiple(array $ids = []);

  /**
   * Load previews for a node keyed by the language codes.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return \Drupal\public_preview\Model\Preview[]|bool
   *   FALSE, if there was a handled exception.
   *   An array of Preview objects or an empty array.
   *
   * @throws \Drupal\Core\Database\InvalidQueryException
   * @throws \Exception
   */
  public function loadForNode($nid);

  /**
   * Deletes public previews according to the conditions.
   *
   * The default implementation performs case-insensitive matching on the
   * 'hash' string.
   *
   * @param array $conditions
   *   An array of criteria.
   *
   * @throws \Exception
   */
  public function delete(array $conditions);

}
