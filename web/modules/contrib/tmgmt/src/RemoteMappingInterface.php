<?php

namespace Drupal\tmgmt;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the tmgmt_remote entity.
 *
 * @ingroup tmgmt_job
 */
interface RemoteMappingInterface extends ContentEntityInterface {

  /**
   * Gets translation job id.
   *
   * @return int
   *   Returns the translation job id.
   */
  public function getJobId();

  /**
   * Gets translation job.
   *
   * @return \Drupal\tmgmt\JobInterface
   *   Returns the translation job.
   */
  public function getJob();

  /**
   * Gets translation job item id.
   *
   * @return int
   *   Returns the job item id.
   */
  public function getJobItemId();

  /**
   * Gets translation job item.
   *
   * @return \Drupal\tmgmt\JobItemInterface
   *   Returns the job item.
   */
  public function getJobItem();

  /**
   * Adds data to the remote_data storage.
   *
   * @param string $key
   *   Key through which the data will be accessible.
   * @param int $value
   *   Value to store.
   */
  public function addRemoteData($key, $value);

  /**
   * Gets data from remote_data storage.
   *
   * @param string $key
   *   Access key for the data.
   *
   * @return mixed
   *   Stored data.
   */
  public function getRemoteData($key);

  /**
   * Removes data from remote_data storage.
   *
   * @param string $key
   *   Access key for the data that are to be removed.
   */
  public function removeRemoteData($key);

  /**
   * Returns the amount.
   *
   * @return int
   *   Returns an integer.
   */
  public function getAmount();

  /**
   * Returns the currency.
   *
   * @return int
   *   Returns an integer.
   */
  public function getCurrency();

  /**
   * Returns the remote identifier 1.
   *
   * @return string
   *   Returns a string.
   */
  public function getRemoteIdentifier1();

  /**
   * Returns the remote identifier 2.
   *
   * @return string
   *   Returns a string.
   */
  public function getRemoteIdentifier2();

  /**
   * Returns the remote identifier 3.
   *
   * @return string
   *   Returns a string.
   */
  public function getRemoteIdentifier3();

  /**
   * Loads remote mappings based on local data.
   *
   * @param int $tjid
   *   Translation job id.
   * @param int $tjiid
   *   Translation job item id.
   * @param int $data_item_key
   *   Data item key.
   *
   * @return static[]
   *   Array of TMGMTRemote entities.
   */
  static public function loadByLocalData($tjid = NULL, $tjiid = NULL, $data_item_key = NULL);

  /**
   * Loads remote mapping entities based on remote identifier.
   *
   * @param string $remote_identifier_1
   *    Remote identifier 1.
   * @param string $remote_identifier_2
   *    Remote identifier 2.
   * @param string $remote_identifier_3
   *    Remote identifier 3.
   *
   * @return static[]
   *   Array of TMGMTRemote entities.
   */
  static public function loadByRemoteIdentifier($remote_identifier_1 = NULL, $remote_identifier_2 = NULL, $remote_identifier_3 = NULL);

}
