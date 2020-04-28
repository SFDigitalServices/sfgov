<?php

namespace Drupal\tmgmt_local\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Render\Element;
use Drupal\tmgmt_local\LocalTaskItemInterface;


/**
 * Entity class for the local task item entity.
 *
 * @ContentEntityType(
 *   id = "tmgmt_local_task_item",
 *   label = @Translation("Translation Task Item"),
 *   handlers = {
 *     "access" = "Drupal\tmgmt_local\Entity\Controller\LocalTaskItemAccessController",
 *     "form" = {
 *       "edit" = "Drupal\tmgmt_local\Form\LocalTaskItemForm"
 *     },
 *     "list_builder" = "Drupal\tmgmt_local\Entity\ListBuilder\LocalTaskItemListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\tmgmt_local\Entity\ViewsData\LocalTaskItemViewsData",
 *   },
 *   base_table = "tmgmt_local_task_item",
 *   entity_keys = {
 *     "id" = "tltiid",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/translate/items/{tmgmt_local_task_item}",
 *   },
 * )
 *
 * @ingroup tmgmt_local_task
 */
class LocalTaskItem extends ContentEntityBase implements LocalTaskItemInterface {

  use EntityChangedTrait;

  /**
   * Holds the unserialized source data.
   *
   * @var array
   */
  protected $unserializedData;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['tltiid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Local Task Item ID'))
      ->setDescription(t('The Local Task Item ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['tltid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Local task'))
      ->setDescription(t('The local task.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_local_task');

    $fields['tjiid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Job Item'))
      ->setDescription(t('The Job Item.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_job_item');

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The job item UUID.'))
      ->setReadOnly(TRUE);

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('The source data'));

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Local task item status'))
      ->setDescription(t('The local task item status'))
      ->setDefaultValue(LocalTaskItemInterface::STATUS_PENDING);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the job was last edited.'));

    $fields['count_untranslated'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Untranslated count'))
      ->setSetting('unsigned', TRUE);

    $fields['count_translated'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Translated count'))
      ->setSetting('unsigned', TRUE);

    $fields['count_completed'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Accepted count'))
      ->setSetting('unsigned', TRUE);

    $fields['word_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Word count'))
      ->setSetting('unsigned', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if ($job_item = $this->getJobItem()) {
      return $job_item->label();
    }
    return t('Missing job item');
  }

  /**
   * {@inheritdoc}
   */
  public function getTask() {
    return $this->get('tltid')->entity;
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
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isPending() {
    return $this->get('status')->value == LocalTaskItemInterface::STATUS_PENDING;
  }

  /**
   * {@inheritdoc}
   */
  public function isCompleted() {
    return $this->get('status')->value == LocalTaskItemInterface::STATUS_COMPLETED;
  }

  /**
   * {@inheritdoc}
   */
  public function isClosed() {
    return $this->get('status')->value == LocalTaskItemInterface::STATUS_CLOSED;
  }

  /**
   * {@inheritdoc}
   */
  public function completed() {
    $this->set('status', LocalTaskItemInterface::STATUS_COMPLETED);
  }

  /**
   * {@inheritdoc}
   */
  public function closed() {
    $this->set('status', LocalTaskItemInterface::STATUS_CLOSED);
  }

  /**
   * {@inheritdoc}
   */
  public function updateData($key, $values = array(), $replace = FALSE) {
    $this->decodeData();
    if ($replace) {
      NestedArray::setValue($this->unserializedData, \Drupal::service('tmgmt.data')->ensureArrayKey($key), $values);
    }
    foreach ($values as $index => $value) {
      // In order to preserve existing values, we can not aplly the values array
      // at once. We need to apply each containing value on its own.
      // If $value is an array we need to advance the hierarchy level.
      if (is_array($value)) {
        $this->updateData(array_merge(\Drupal::service('tmgmt.data')->ensureArrayKey($key), array($index)), $value);
      }
      // Apply the value.
      else {
        NestedArray::setValue($this->unserializedData, array_merge(\Drupal::service('tmgmt.data')->ensureArrayKey($key), array($index)), $value, TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key = array(), $index = NULL) {
    $this->decodeData();
    if (empty($this->unserializedData) && $this->getTask()) {
      // Load the data from the source if it has not been set yet.
      $this->unserializedData = $this->getJobItem()->getData();
      $this->save();
    }
    if (empty($key)) {
      return $this->unserializedData;
    }
    if ($index) {
      $key = array_merge($key, array($index));
    }
    return NestedArray::getValue($this->unserializedData, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function getCountTranslated() {
    return $this->get('count_translated')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountUntranslated() {
    return $this->get('count_untranslated')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountCompleted() {
    return $this->get('count_completed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getWordCount() {
    return $this->get('word_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->getTask()) {
      $this->recalculateStatistics();
    }
    if ($this->unserializedData) {
      $this->set('data', Json::encode($this->unserializedData));
    }
    elseif (empty($this->get('data')->value)) {
      $this->set('data', Json::encode(array()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function recalculateStatistics() {
    // Set translatable data from the current entity to calculate words.
    $this->decodeData();

    if (empty($this->unserializedData)) {
      $this->unserializedData = $this->getJobItem()->getData();
    }

    // Consider everything accepted when the job item is accepted.
    if ($this->isCompleted() || $this->isClosed()) {
      $this->set('count_translated', 0);
      $this->set('count_completed', count(array_filter(\Drupal::service('tmgmt.data')->flatten($this->unserializedData), array(\Drupal::service('tmgmt.data'), 'filterData'))));
      $this->set('count_untranslated', 0);
    }
    // Count the data item states.
    else {
      // Reset counter values.
      $this->set('count_translated', 0);
      $this->set('count_completed', 0);
      $this->set('word_count', 0);
      $this->set('count_untranslated', count(array_filter(\Drupal::service('tmgmt.data')->flatten($this->unserializedData), array(\Drupal::service('tmgmt.data'), 'filterData'))));
      $this->count($this->unserializedData);
    }
  }

  /**
   * Counts accepted, translated and pending items.
   *
   * Parse all data items recursively and sums up the counters for
   * accepted, translated and pending items.
   *
   * @param array $item
   *   The current data item.
   */
  protected function count(array &$item) {
    if (!empty($item['#text'])) {
      if (\Drupal::service('tmgmt.data')->filterData($item)) {

        // Count words of the data item.
        $text = is_array($item['#text']) ? $item['#text']['value'] : $item['#text'];
        $this->set('word_count', $this->get('word_count')->value + \Drupal::service('tmgmt.data')->wordCount($text));

        // Set default states if no state is set.
        if (!isset($item['#status'])) {
          $item['#status'] = TMGMT_DATA_ITEM_STATE_UNTRANSLATED;
        }
        switch ($item['#status']) {
          case TMGMT_DATA_ITEM_STATE_TRANSLATED:
            $this->set('count_untranslated', $this->get('count_untranslated')->value - 1);
            $this->set('count_translated', $this->get('count_translated')->value + 1);
            break;
        }
      }
    }
    else {
      foreach (Element::children($item) as $key) {
        $this->count($item[$key]);
      }
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
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    $tags = $this->getTask()->getEntityType()->getListCacheTags();
    if ($update) {
      $tags = Cache::mergeTags($tags, $this->getTask()->getCacheTagsToInvalidate());
    }
    Cache::invalidateTags($tags);
  }

  /**
   * Ensures that the data is decoded.
   */
  protected function decodeData() {
    if (empty($this->unserializedData) && $this->get('data')->value) {
      $this->unserializedData = (array) Json::decode($this->get('data')->value);
    }
    if (!is_array($this->unserializedData)) {
      $this->unserializedData = [];
    }
  }

  /**
   * Retrieve a labeled list of all available statuses for task items.
   *
   * @return array
   *   A list of all available statuses.
   */
  public static function getStatuses() {
    return array(
      LocalTaskItemInterface::STATUS_PENDING => t('Untranslated'),
      LocalTaskItemInterface::STATUS_COMPLETED => t('Translated'),
      LocalTaskItemInterface::STATUS_REJECTED => t('Rejected'),
      LocalTaskItemInterface::STATUS_CLOSED => t('Completed'),
    );
  }

}
