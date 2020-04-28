<?php

namespace Drupal\tmgmt_local\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\user\UserInterface;
use Drupal\tmgmt_local\LocalTaskInterface;
use Drupal\tmgmt\Entity\Job;

/**
 * Entity class for the local task entity.
 *
 * @ContentEntityType(
 *   id = "tmgmt_local_task",
 *   label = @Translation("Translation Task"),
 *   handlers = {
 *     "access" = "Drupal\tmgmt_local\Entity\Controller\LocalTaskAccessControlHandler",
 *     "form" = {
 *       "edit" = "Drupal\tmgmt_local\Form\LocalTaskForm",
 *       "assign" = "Drupal\tmgmt_local\Form\LocalTaskAssignForm",
 *       "unassign" = "Drupal\tmgmt_local\Form\LocalTaskUnassignForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\tmgmt_local\Entity\ListBuilder\LocalTaskListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\tmgmt_local\Entity\ViewsData\LocalTaskViewsData",
 *   },
 *   base_table = "tmgmt_local_task",
 *   entity_keys = {
 *     "id" = "tltid",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/translate/{tmgmt_local_task}",
 *     "assign" = "/translate/{tmgmt_local_task}/assign",
 *     "assign_to_me" = "/translate/{tmgmt_local_task}/assign_to_me",
 *     "unassign" = "/translate/{tmgmt_local_task}/unassign",
 *     "delete" = "/translate/{tmgmt_local_task}/delete",
 *   }
 * )
 *
 * @ingroup tmgmt_local_task
 */
class LocalTask extends ContentEntityBase implements LocalTaskInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['tltid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Local task ID'))
      ->setDescription(t('The local task ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['tjid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Job'))
      ->setDescription(t('The Job for this task.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_job')
      ->setDefaultValue(0);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The node UUID.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of this local task.'))
      ->setDefaultValue('')
      ->setSettings(array(
        'max_length' => 255,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user that created the local task.'))
      ->setSettings(array(
        'target_type' => 'user',
      ))
      ->setDefaultValue(0);

    $fields['tuid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Assigned user'))
      ->setDescription(t('The user assigned to this task.'))
      ->setSettings(array(
        'target_type' => 'user',
      ))
      ->setDefaultValue(0);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('The local task status.'))
      ->setDefaultValue(static::STATUS_UNASSIGNED);

    $fields['loop_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Loop count'))
      ->setDescription(t('Counter for how many times task was returned to the assigned user.'))
      ->setDefaultValue(static::STATUS_UNASSIGNED);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the job was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the job was last edited.'));
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignee() {
    return $this->get('tuid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (!$this->get('title')->value) {
      return $this->getJob()->label();
    }
    else {
      return $this->get('title')->value;
    }
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
  public function assign(AccountInterface $user) {
    $this->incrementLoopCount(static::STATUS_PENDING, $user->id());
    $this->set('tuid', $user->id());
    $this->set('status', static::STATUS_PENDING);
  }

  /**
   * {@inheritdoc}
   */
  public function unassign() {
    // We also need to increment loop count when unassigning.
    $this->incrementLoopCount(static::STATUS_UNASSIGNED, 0);
    $this->set('tuid', 0);
    $this->set('status', static::STATUS_UNASSIGNED);
  }

  /**
   * {@inheritdoc}
   */
  public function getItems($conditions = array()) {
    $query = \Drupal::entityQuery('tmgmt_local_task_item');
    $query->condition('tltid', $this->id());
    foreach ($conditions as $key => $condition) {
      if (is_array($condition)) {
        $operator = isset($condition['operator']) ? $condition['operator'] : '=';
        $query->condition($key, $condition['value'], $operator);
      }
      else {
        $query->condition($key, $condition);
      }
    }
    $query->sort('tltiid');
    $results = $query->execute();
    if (!empty($results)) {
      return LocalTaskItem::loadMultiple($results);
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function addTaskItem(JobItemInterface $job_item) {
    // Save the task to get an id.
    if ($this->isNew()) {
      $this->save();
    }

    $local_task = LocalTaskItem::create(array(
      'tltid' => $this->id(),
      'tjiid' => $job_item->id(),
    ));
    $local_task->save();
    return $local_task;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status, $message = NULL, $variables = array(), $type = 'debug') {
    // Return TRUE if the status could be set. Return FALSE otherwise.
    if (array_key_exists($status, $this->getStatuses())) {
      $this->incrementLoopCount($status, $this->tuid->target_id);
      $this->status = $status;
      $this->save();
    }
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function isStatus($status) {
    return $this->getStatus() == $status;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthor(AccountInterface $account = NULL) {
    $account = isset($account) ? $account : \Drupal::currentUser();
    return $this->getOwnerId() == $account->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isUnassigned() {
    return $this->isStatus(static::STATUS_UNASSIGNED);
  }

  /**
   * {@inheritdoc}
   */
  public function isPending() {
    return $this->isStatus(static::STATUS_PENDING);
  }

  /**
   * {@inheritdoc}
   */
  public function isCompleted() {
    return $this->isStatus(static::STATUS_COMPLETED);
  }

  /**
   * {@inheritdoc}
   */
  public function isRejected() {
    return $this->isStatus(static::STATUS_REJECTED);
  }

  /**
   * {@inheritdoc}
   */
  public function isClosed() {
    return $this->isStatus(static::STATUS_CLOSED);
  }

  /**
   * {@inheritdoc}
   */
  public function getCountTranslated() {
    return tmgmt_local_task_statistic($this, 'count_translated');
  }

  /**
   * {@inheritdoc}
   */
  public function getCountUntranslated() {
    return tmgmt_local_task_statistic($this, 'count_untranslated');
  }

  /**
   * {@inheritdoc}
   */
  public function getCountCompleted() {
    return tmgmt_local_task_statistic($this, 'count_completed');
  }

  /**
   * {@inheritdoc}
   */
  public function getWordCount() {
    return tmgmt_local_task_statistic($this, 'word_count');
  }


  /**
   * {@inheritdoc}
   */
  public function getLoopCount() {
    return $this->loop_count->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementLoopCount($new_status, $new_tuid) {
    if ($this->getStatus() == static::STATUS_PENDING
      && $new_status == static::STATUS_PENDING
      && $this->tuid->target_id != $new_tuid
    ) {
      ++$this->loop_count->value;
    }
    else {
      if ($this->getStatus() != static::STATUS_UNASSIGNED
        && $new_status == static::STATUS_UNASSIGNED
      ) {
        ++$this->loop_count->value;
      }
      else {
        if ($this->getStatus() != static::STATUS_UNASSIGNED
          && $this->getStatus() != static::STATUS_PENDING
          && $new_status == static::STATUS_PENDING
        ) {
          ++$this->loop_count->value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    $ids = \Drupal::entityQuery('tmgmt_local_task_item')
      ->condition('tltid', array_keys($entities), 'IN')
      ->execute();
    if (!empty($ids)) {
      $storage_handler = \Drupal::entityTypeManager()->getStorage('tmgmt_local_task_item');
      $entities = $storage_handler->loadMultiple($ids);
      $storage_handler->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations() {
    return $this->getChangedTime();
  }

  /**
   * {@inheritdoc}
   */
  public static function getStatuses() {
    return array(
      static::STATUS_UNASSIGNED => t('Unassigned'),
      static::STATUS_PENDING => t('Pending'),
      static::STATUS_COMPLETED => t('Completed'),
      static::STATUS_REJECTED => t('Rejected'),
      static::STATUS_CLOSED => t('Closed'),
    );
  }

}
