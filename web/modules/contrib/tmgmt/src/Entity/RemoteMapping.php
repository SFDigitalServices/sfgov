<?php

namespace Drupal\tmgmt\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\tmgmt\RemoteMappingInterface;
use Drupal\tmgmt\Entity\Job;

/**
 * Entity class for the tmgmt_remote entity.
 *
 * @ContentEntityType(
 *   id = "tmgmt_remote",
 *   label = @Translation("Translation Remote Mapping"),
 *   base_table = "tmgmt_remote",
 *   entity_keys = {
 *     "id" = "trid",
 *     "uuid" = "uuid"
 *   }
 * )
 *
 * @ingroup tmgmt_job
 */
class RemoteMapping extends ContentEntityBase implements RemoteMappingInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['trid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Remote mapping ID'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The node UUID.'))
      ->setReadOnly(TRUE);
    $fields['tjid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Job reference'))
      ->setSetting('target_type', 'tmgmt_job');
    $fields['tjiid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Job item reference'))
      ->setSetting('target_type', 'tmgmt_job_item');
    $fields['data_item_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Data Item Key'));
    $fields['remote_identifier_1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote identifier 1'));
    $fields['remote_identifier_2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote identifier 2'));
    $fields['remote_identifier_3'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote identifier 3'));
    $fields['remote_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Remote URL'));
    $fields['word_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Word count'))
      ->setDescription(t('Word count provided by the remote service.'));
    $fields['tags_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tags count'))
      ->setDescription(t('HTML tags count provided by the remote service.'));
    $fields['amount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Amount'))
      ->setDescription(t('Amount charged for the remote translation job.'));
    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'));
    $fields['remote_data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Remote data'));
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobId() {
    return $this->get('tjid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getJob() {
    return Job::load($this->get('tjid')->target_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getJobItemId() {
    return $this->get('tjiid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobItem() {
    return $this->get('tjiid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function addRemoteData($key, $value) {
    $this->remote_data->$key = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteData($key) {
    return $this->remote_data->$key;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRemoteData($key) {
    unset($this->remote_data->$key);
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    return $this->get('amount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->get('currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteIdentifier1() {
    return $this->get('remote_identifier_1')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteIdentifier2() {
    return $this->get('remote_identifier_2')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteIdentifier3() {
    return $this->get('remote_identifier_3')->value;
  }

  /**
   * {@inheritdoc}
   */
  static public function loadByLocalData($tjid = NULL, $tjiid = NULL, $data_item_key = NULL) {
    $data_item_key = \Drupal::service('tmgmt.data')->ensureStringKey($data_item_key);

    $query = \Drupal::entityQuery('tmgmt_remote');
    if (!empty($tjid)) {
      $query->condition('tjid', $tjid);
    }
    if (!empty($tjiid)) {
      $query->condition('tjiid', $tjiid);
    }
    if (!empty($data_item_key)) {
      $query->condition('data_item_key', $data_item_key);
    }

    $trids = $query->execute();
    if (!empty($trids)) {
      return static::loadMultiple($trids);
    }

    return array();
  }

  /**
   * {@inheritdoc}
   */
  static public function loadByRemoteIdentifier($remote_identifier_1 = NULL, $remote_identifier_2 = NULL, $remote_identifier_3 = NULL) {
    $query = \Drupal::entityQuery('tmgmt_remote');
    if ($remote_identifier_1 !== NULL) {
      $query->condition('remote_identifier_1', $remote_identifier_1);
    }
    if ($remote_identifier_2 !== NULL) {
      $query->condition('remote_identifier_2', $remote_identifier_2);
    }
    if ($remote_identifier_3 !== NULL) {
      $query->condition('remote_identifier_3', $remote_identifier_3);
    }
    $trids = $query->execute();
    if (!empty($trids)) {
      return static::loadMultiple($trids);
    }

    return array();
  }

}
