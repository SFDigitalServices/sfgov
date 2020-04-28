<?php

namespace Drupal\tmgmt\Entity;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\Core\Render\Element;

/**
 * Entity class for the tmgmt_job_item entity.
 *
 * @ContentEntityType(
 *   id = "tmgmt_job_item",
 *   label = @Translation("Translation Job Item"),
 *   module = "tmgmt",
 *   handlers = {
 *     "access" = "Drupal\tmgmt\Entity\Controller\JobItemAccessControlHandler",
 *     "form" = {
 *       "edit" = "Drupal\tmgmt\Form\JobItemForm",
 *       "abort" = "Drupal\tmgmt\Form\JobItemAbortForm",
 *       "delete" = "Drupal\tmgmt\Form\JobItemDeleteForm"
 *     },
 *     "list_builder" = "Drupal\tmgmt\Entity\ListBuilder\JobItemListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\tmgmt\Entity\ViewsData\JobItemViewsData"
 *   },
 *   base_table = "tmgmt_job_item",
 *   entity_keys = {
 *     "id" = "tjiid",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/tmgmt/items/{tmgmt_job_item}",
 *     "abort-form" = "/admin/tmgmt/items/{tmgmt_job_item}/abort",
 *     "delete-form" = "/admin/tmgmt/items/{tmgmt_job_item}/delete",
 *   },
 * )
 *
 * @ingroup tmgmt_job
 */
class JobItem extends ContentEntityBase implements JobItemInterface {

  /**
   * Holds the unserialized source data.
   *
   * @var array
   */
  protected $unserializedData;

  /**
   * Statically cached state definitions.
   *
   * @var
   */
  protected static $stateDefinitions;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['tjiid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Job Item ID'))
      ->setDescription(t('The Job Item ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['tjid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Job'))
      ->setDescription(t('The Job.'))
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'tmgmt_job')
      ->setDefaultValue(0);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The job item UUID.'))
      ->setReadOnly(TRUE);

    $fields['plugin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin'))
      ->setDescription(t('The plugin of this job item.'))
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['item_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item Type'))
      ->setDescription(t('The item type of this job item.'))
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['item_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item ID'))
      ->setDescription(t('The item ID of this job item.'))
      ->setSettings(array(
        'max_length' => 255,
      ));

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('The source data'));

    $states = static::getStates();
    $fields['state'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Job item state'))
      ->setDescription(t('The job item state'))
      ->setSetting('allowed_values', $states)
      ->setDefaultValue(static::STATE_INACTIVE);

    $fields['translator_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Translator job item state'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the job was last edited.'));

    $fields['count_pending'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Pending count'))
      ->setSetting('unsigned', TRUE);

    $fields['count_translated'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Translated count'))
      ->setSetting('unsigned', TRUE);

    $fields['count_reviewed'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Reviewed count'))
      ->setSetting('unsigned', TRUE);

    $fields['count_accepted'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Accepted count'))
      ->setSetting('unsigned', TRUE);

    $fields['word_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Word count'))
      ->setSetting('unsigned', TRUE);

    $fields['tags_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tags count'))
      ->setSetting('unsigned', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function cloneAsActive() {
    $clone = $this->createDuplicate();
    $clone->data->value = NULL;
    $clone->unserializedData = NULL;
    $clone->tjid->target_id = 0;
    $clone->tjiid->value = 0;
    $clone->word_count->value = NULL;
    $clone->tags_count->value = NULL;
    $clone->count_accepted->value = NULL;
    $clone->count_pending->value = NULL;
    $clone->count_translated->value = NULL;
    $clone->count_reviewed->value = NULL;
    $clone->state->value = static::STATE_ACTIVE;
    return $clone;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->getJobId()) {
      $this->recalculateStatistics();
      drupal_static_reset('tmgmt_job_statistics_load');
    }
    if ($this->unserializedData) {
      $this->data = Json::encode($this->unserializedData);
    }
    elseif (empty($this->get('data')->value)) {
      $this->data = Json::encode(array());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    // We need to check whether the state of the job is affected by this
    // deletion.
    foreach ($entities as $entity) {
      if ($job = $entity->getJob()) {
        // We only care for active jobs.
        if ($job->isActive() && tmgmt_job_check_finished($job->id())) {
          // Mark the job as finished.
          $job->finished();
        }
      }
    }
    parent::preDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    $entity_type_manager = \Drupal::entityTypeManager();

    // Since we are deleting one or multiple job items here we also need to
    // delete the attached messages.
    $mids = \Drupal::entityQuery('tmgmt_message')
      ->condition('tjiid', array_keys($entities), 'IN')
      ->execute();
    if (!empty($mids)) {
      $messages = $entity_type_manager->getStorage('tmgmt_message')->loadMultiple($mids);
      $entity_type_manager->getStorage('tmgmt_message')->delete($messages);
    }

    $trids = \Drupal::entityQuery('tmgmt_remote')
      ->condition('tjiid', array_keys($entities), 'IN')
      ->execute();
    if (!empty($trids)) {
      $remotes = $entity_type_manager->getStorage('tmgmt_remote')->loadMultiple($trids);
      $entity_type_manager->getStorage('tmgmt_remote')->delete($remotes);
    }
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
  public function getPlugin() {
    return $this->get('plugin')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemType() {
    return $this->get('item_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId() {
    return $this->get('item_id')->value;
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
  public function label($langcode = NULL) {
    $label = $this->getSourceLabel() ?: parent::label();
    if (strlen($label) > Job::LABEL_MAX_LENGTH) {
      $label = Unicode::truncate($label, Job::LABEL_MAX_LENGTH, TRUE);
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $variables = array(), $type = 'status') {
    // Save the job item if it hasn't yet been saved.
    if (!$this->isNew() || $this->save()) {
      $message = tmgmt_message_create($message,
        $variables,
        array(
          'tjid' => $this->getJobId(),
          'tjiid' => $this->id(),
          'type' => $type,
        )
      );
      if ($message->save()) {
        return $message;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLabel() {
    if ($plugin = $this->getSourcePlugin()) {
      return (string) $plugin->getLabel($this);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceUrl() {
    if ($plugin = $this->getSourcePlugin()) {
      return $plugin->getUrl($this);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceType() {
    if ($plugin = $this->getSourcePlugin()) {
      return $plugin->getType($this);
    }
    return ucfirst($this->get('item_type')->value);
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
  public function getTranslator() {
    if ($this->hasTranslator()) {
      return $this->getJob()->getTranslator();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslator() {
    if ($this->getJob() && $this->getJob()->hasTranslator()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatorPlugin() {
    if ($job = $this->getJob()) {
      return $job->getTranslatorPlugin();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key = array(), $index = NULL) {
    $this->decodeData();
    if (empty($this->unserializedData) && $this->getJobId()) {
      // Load the data from the source if it has not been set yet.
      $this->unserializedData = $this->getSourceData();
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
  public function getSourceData() {
    if ($plugin = $this->getSourcePlugin()) {
      $data = $plugin->getData($this);
      /** @var \Drupal\tmgmt\SegmenterInterface $segmenter */
      $segmenter = \Drupal::service('tmgmt.segmenter');
      return $segmenter->getSegmentedData($data);
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePlugin() {
    if ($this->get('plugin')->value) {
      try {
        return \Drupal::service('plugin.manager.tmgmt.source')->createInstance($this->get('plugin')->value);
      }
      catch (PluginException $e) {
        // Ignore exceptions due to missing source plugins.
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountPending() {
    return $this->get('count_pending')->value;
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
  public function getCountAccepted() {
    return $this->get('count_accepted')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountReviewed() {
    return $this->get('count_reviewed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getWordCount() {
    return (int) $this->get('word_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTagsCount() {
    return (int) $this->get('tags_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function needsReview($message = NULL, $variables = array(), $type = 'status') {
    if (!isset($message)) {
      $source_url = $this->getSourceUrl();
      $message = $source_url ? 'The translation for <a href=":source_url">@source</a> needs to be reviewed.' : 'The translation for @source needs to be reviewed.';
      $variables = $source_url ? array(
        ':source_url' => $source_url,
        '@source' => ($this->getSourceLabel()),
      ) : array('@source' => ($this->getSourceLabel()));
    }
    $return = $this->setState(static::STATE_REVIEW, $message, $variables, $type);
    // Auto accept the translation if the translator is configured for it.
    if ($this->getTranslator()->isAutoAccept() && !$this->isAborted()) {
      $this->acceptTranslation();
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function accepted($message = NULL, $variables = array(), $type = 'status') {
    if (!isset($message)) {
      $source_url = $this->getSourceUrl();
      try {
        $translation = \Drupal::entityTypeManager()->getStorage($this->getItemType())->load($this->getItemId());
      }
      catch (PluginNotFoundException $e) {
        $translation = NULL;
      }
      if (isset($translation)) {
        $translation = $translation->getTranslation($this->getJob()->getTargetLangcode());
        try {
          $translation_url = $translation->toUrl();
        }
        catch (UndefinedLinkTemplateException $e) {
          $translation_url = NULL;
        }
        $message = $source_url && $translation_url ? 'The translation for <a href=":source_url">@source</a> has been accepted as <a href=":target_url">@target</a>.' : 'The translation for @source has been accepted as @target.';
        $variables = $translation_url ? array(
          ':source_url' => $source_url->toString(),
          '@source' => ($this->getSourceLabel()),
          ':target_url' => $translation_url->toString(),
          '@target' => $translation ? $translation->label() : $this->getSourceLabel(),
        ) : array('@source' => ($this->getSourceLabel()), '@target' => ($translation ? $translation->label() : $this->getSourceLabel()));
      }
      else {
        $message   = $source_url ? 'The translation for <a href=":source_url">@source</a> has been accepted.' : 'The translation for @source has been accepted.';
        $variables = $source_url ? array(
          ':source_url' => $source_url->toString(),
          '@source'     => ($this->getSourceLabel()),
        ) : array('@source' => ($this->getSourceLabel()));
      }
    }
    $return = $this->setState(static::STATE_ACCEPTED, $message, $variables, $type);
    // Check if this was the last unfinished job item in this job.
    $job = $this->getJob();
    if ($job && !$job->isContinuous() && tmgmt_job_check_finished($this->getJobId())) {
      // Mark the job as finished in case it is a normal job.
      $job->finished();
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function active($message = NULL, $variables = array(), $type = 'status') {
    if (!isset($message)) {
      $source_url = $this->getSourceUrl();
      $message = $source_url ? 'The translation for <a href=":source_url">@source</a> is now being processed.' : 'The translation for @source is now being processed.';
      $variables = $source_url ? array(
        ':source_url' => $source_url->toString(),
        '@source' => ($this->getSourceLabel()),
      ) : array('@source' => ($this->getSourceLabel()));
    }
    return $this->setState(static::STATE_ACTIVE, $message, $variables, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state, $message = NULL, $variables = array(), $type = 'debug') {
    // Return TRUE if the state could be set. Return FALSE otherwise.
    if (array_key_exists($state, JobItem::getStates()) && $this->get('state')->value != $state) {
      $this->set('state', $state);
      // Changing the state resets the translator state.
      $this->setTranslatorState(NULL);
      $this->save();
      // If a message is attached to this state change add it now.
      if (!empty($message)) {
        $this->addMessage($message, $variables, $type);
      }
    }
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isState($state) {
    return $this->getState() == $state;
  }

  /**
   * {@inheritdoc}
   */
  public function isAccepted() {
    return $this->isState(static::STATE_ACCEPTED);
  }

  /**
   *
   * @return boolean
   */
  public function isAbortable() {
    if ($this->isActive() || $this->isNeedsReview()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->isState(static::STATE_ACTIVE);
  }

  /**
   * {@inheritdoc}
   */
  public function isNeedsReview() {
    return $this->isState(static::STATE_REVIEW);
  }

  /**
   * {@inheritdoc}
   */
  public function isAborted() {
    return $this->isState(static::STATE_ABORTED);
  }

  /**
   * {@inheritdoc}
   */
  public function isInactive() {
    return $this->isState(static::STATE_INACTIVE);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatorState() {
    return $this->get('translator_state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslatorState($translator_state = NULL) {
    $this->get('translator_state')->value = $translator_state;
    return $this;
  }

  /**
   * Recursively writes translated data to the data array of a job item.
   *
   * While doing this the #status of each data item is set to
   * TMGMT_DATA_ITEM_STATE_TRANSLATED.
   *
   * @param array $translation
   *   Nested array of translated data. Can either be a single text entry, the
   *   whole data structure or parts of it.
   * @param array|string $key
   *   (Optional) Either a flattened key (a 'key1][key2][key3' string) or a
   *   nested one, e.g. array('key1', 'key2', 'key2'). Defaults to an empty
   *   array which means that it will replace the whole translated data array.
   * @param int|null $status
   *   (Optional) The data item status that will be set. Defaults to NULL,
   *   which means that it will be set to translated unless it was previously
   *   set to preliminary, then it will keep that state.
   *   Explicitly pass TMGMT_DATA_ITEM_STATE_TRANSLATED,
   *   TMGMT_DATA_ITEM_STATE_PRELIMINARY or TMGMT_DATA_ITEM_STATE_REVIEWED to
   *   set it to that value. Other statuses are not supported.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   If is given an unsupported status.
   */
  protected function addTranslatedDataRecursive(array $translation, $key = array(), $status = NULL) {
    if ($status != NULL && !in_array($status, [TMGMT_DATA_ITEM_STATE_PRELIMINARY, TMGMT_DATA_ITEM_STATE_TRANSLATED, TMGMT_DATA_ITEM_STATE_REVIEWED])) {
      new TMGMTException('Unsupported status given.');
    }
    if (isset($translation['#text'])) {
      $data_service = \Drupal::service('tmgmt.data');
      $data = $this->getData($data_service->ensureArrayKey($key));
      if (empty($data['#status']) || $data['#status'] != TMGMT_DATA_ITEM_STATE_ACCEPTED) {

        // In case the origin is not set consider it to be remote.
        if (!isset($translation['#origin'])) {
          $translation['#origin'] = 'remote';
        }

        // If we already have a translation text and it hasn't changed, don't
        // update anything unless the origin is remote.
        if (!empty($data['#translation']['#text']) && $data['#translation']['#text'] == $translation['#text'] && $translation['#origin'] != 'remote') {
          return;
        }

        // In case the timestamp is not set consider it to be now.
        if (!isset($translation['#timestamp'])) {
          $translation['#timestamp'] = REQUEST_TIME;
        }
        // If we have a translation text and is different from new one create
        // revision.
        if (!empty($data['#translation']['#text']) && $data['#translation']['#text'] != $translation['#text']) {

          // Copy into $translation existing revisions.
          if (!empty($data['#translation']['#text_revisions'])) {
            $translation['#text_revisions'] = $data['#translation']['#text_revisions'];
          }

          // If current translation was created locally and the incoming one is
          // remote, do not override the local, just create a new revision.
          if (isset($data['#translation']['#origin']) && $data['#translation']['#origin'] == 'local' && $translation['#origin'] == 'remote') {
            $translation['#text_revisions'][] = array(
              '#text' => $translation['#text'],
              '#origin' => $translation['#origin'],
              '#timestamp' => $translation['#timestamp'],
            );
            $this->addMessage('Translation for customized @key received. Revert your changes if you wish to use it.', array('@key' => $data_service->ensureStringKey($key)));
            // Unset text and origin so that the current translation does not
            // get overridden.
            unset($translation['#text'], $translation['#origin'], $translation['#timestamp']);
          }
          // Do the same if the translation was already reviewed and origin is
          // remote.
          elseif ($translation['#origin'] == 'remote' && !empty($data['#status']) && $data['#status'] == TMGMT_DATA_ITEM_STATE_REVIEWED) {
            $translation['#text_revisions'][] = array(
              '#text' => $translation['#text'],
              '#origin' => $translation['#origin'],
              '#timestamp' => $translation['#timestamp'],
            );
            $this->addMessage('Translation for already reviewed @key received and stored as a new revision. Revert to it if you wish to use it.', array('@key' => $data_service->ensureStringKey($key)));
            // Unset text and origin so that the current translation does not
            // get overridden.
            unset($translation['#text'], $translation['#origin'], $translation['#timestamp']);
          }
          else {
            $translation['#text_revisions'][] = array(
              '#text' => $data['#translation']['#text'],
              '#origin' => isset($data['#translation']['#origin']) ? $data['#translation']['#origin'] : 'remote',
              '#timestamp' => isset($data['#translation']['#timestamp']) ? $data['#translation']['#timestamp'] : $this->getChangedTime(),
            );
            // Add a message if the translation update is from remote.
            if ($translation['#origin'] == 'remote') {
              $diff = mb_strlen($translation['#text']) - mb_strlen($data['#translation']['#text']);
              $this->addMessage('Updated translation for key @key, size difference: @diff characters.', array('@key' => $data_service->ensureStringKey($key), '@diff' => $diff));
            }
          }
        }

        if ($status == NULL) {
          if (isset($data['#status']) && $data['#status'] == TMGMT_DATA_ITEM_STATE_PRELIMINARY) {
            $status = TMGMT_DATA_ITEM_STATE_PRELIMINARY;
          }
          else {
            $status = TMGMT_DATA_ITEM_STATE_TRANSLATED;
          }
        }

        $values = array(
          '#translation' => $translation,
          '#status' => $status,
        );
        $this->updateData($key, $values);
      }
      return;
    }

    foreach (Element::children($translation) as $item) {
      $this->addTranslatedDataRecursive($translation[$item], array_merge($key, array($item)), $status);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dataItemRevert(array $key) {
    $data = $this->getData($key);
    if (!empty($data['#translation']['#text_revisions'])) {

      $prev_revision = end($data['#translation']['#text_revisions']);
      $data['#translation']['#text_revisions'][] = array(
        '#text' => $data['#translation']['#text'],
        '#timestamp' => $data['#translation']['#timestamp'],
        '#origin' => $data['#translation']['#origin'],
      );
      $data['#translation']['#text'] = $prev_revision['#text'];
      $data['#translation']['#origin'] = $prev_revision['#origin'];
      $data['#translation']['#timestamp'] = $prev_revision['#timestamp'];

      $this->updateData($key, $data);
      $this->addMessage('Translation for @key reverted to the latest version.', array('@key' => $key[0]));
      return TRUE;
    }

    return FALSE;
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
        NestedArray::setValue($this->unserializedData, array_merge(\Drupal::service('tmgmt.data')->ensureArrayKey($key), array($index)), $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetData() {
    $this->data->value = NULL;
    $this->unserializedData = NULL;
    $this->getData();
  }

  /**
   * {@inheritdoc}
   */
  public function addTranslatedData(array $translation, $key = array(), $status = NULL) {

    if ($this->isInactive()) {
      // The job item can not be inactive and receive translations.
      $this->setState(JobItemInterface::STATE_ACTIVE);
    }

    $this->addTranslatedDataRecursive($translation, $key, $status);
    // Check if the job item has all the translated data that it needs now.
    // Only attempt to change the status to needs review if it is currently
    // active.
    if ($this->isActive()) {
      $data = \Drupal::service('tmgmt.data')->filterTranslatable($this->getData());
      $finished = TRUE;
      foreach ($data as $item) {
        if (empty($item['#status']) || $item['#status'] == TMGMT_DATA_ITEM_STATE_PENDING || $item['#status'] == TMGMT_DATA_ITEM_STATE_PRELIMINARY) {
          $finished = FALSE;
          break;
        }
      }
      if ($finished && $this->getJob()->hasTranslator()) {
        // There are no unfinished elements left.
        if ($this->getJob()->getTranslator()->isAutoAccept()) {
          // If the job item is going to be auto-accepted, set to review without
          // a message.
          $this->needsReview(FALSE);
        }
        else {
          // Otherwise, create a message that contains source label, target
          // language and links to the review form.
          $job_url = $this->getJob()->toUrl()->toString();
          $variables = array(
            '@source' => $this->getSourceLabel(),
            '@language' => $this->getJob()->getTargetLanguage()->getName(),
            ':review_url' => $this->toUrl('canonical', array('query' => array('destination' => $job_url)))->toString(),
          );
          (!$this->getSourceUrl()) ? $variables[':source_url'] = (string) $job_url : $variables[':source_url'] = $this->getSourceUrl()->toString();
          $this->needsReview('The translation of <a href=":source_url">@source</a> to @language is finished and can now be <a href=":review_url">reviewed</a>.', $variables);
        }
      }
    }
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function acceptTranslation() {
    if (!$this->isNeedsReview() || !$plugin = $this->getSourcePlugin()) {
      return FALSE;
    }
    if (!$plugin->saveTranslation($this, $this->getJob()->getTargetLangcode())) {
      return FALSE;
    }
    // If the plugin could save the translation, we will set it
    // to the 'accepted' state.
    $this->accepted();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation() {
    if (!$this->isActive() || !$this->getTranslatorPlugin()) {
      throw new TMGMTException('Cannot abort job item.');
    }
    $this->setState(JobItemInterface::STATE_ABORTED);
    // Check if this was the last unfinished job item in this job.
    $job = $this->getJob();
    if ($job && !$job->isContinuous() && tmgmt_job_check_finished($this->getJobId())) {
      // Mark the job as finished in case it is a normal job.
      $job->finished();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages($conditions = array()) {
    $query = \Drupal::entityQuery('tmgmt_message')
      ->condition('tjiid', $this->id());
    foreach ($conditions as $key => $condition) {
      if (is_array($condition)) {
        $operator = isset($condition['operator']) ? $condition['operator'] : '=';
        $query->condition($key, $condition['value'], $operator);
      }
      else {
        $query->condition($key, $condition);
      }
    }
    $results = $query->execute();
    if (!empty($results)) {
      return Message::loadMultiple($results);
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getSiblings() {
    $ids = \Drupal::entityQuery('tmgmt_job_item')
      ->condition('tjiid', $this->id(), '<>')
      ->condition('tjid', $this->getJobId())
      ->execute();
    if ($ids) {
      return static::loadMultiple($ids);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessagesSince($time = NULL) {
    $time = isset($time) ? $time : REQUEST_TIME;
    $conditions = array('created' => array('value' => $time, 'operator' => '>='));
    return $this->getMessages($conditions);
  }


  /**
   * {@inheritdoc}
   */
  public function addRemoteMapping($data_item_key = NULL, $remote_identifier_1 = NULL, $mapping_data = array()) {

    if (empty($remote_identifier_1) && !isset($mapping_data['remote_identifier_2']) && !isset($remote_mapping['remote_identifier_3'])) {
      throw new TMGMTException('Cannot create remote mapping without remote identifier.');
    }

    $data = array(
      'tjid' => $this->getJobId(),
      'tjiid' => $this->id(),
      'data_item_key' => $data_item_key,
      'remote_identifier_1' => $remote_identifier_1,
    );

    if (!empty($mapping_data)) {
      $data += $mapping_data;
    }

    $remote_mapping = RemoteMapping::create($data);

    return $remote_mapping->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteMappings() {
    $trids = \Drupal::entityQuery('tmgmt_remote')
      ->condition('tjiid', $this->id())
      ->execute();

    if (!empty($trids)) {
      return RemoteMapping::loadMultiple($trids);
    }

    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLangCode() {
    return $this->getSourcePlugin()->getSourceLangCode($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingLangCodes() {
    return $this->getSourcePlugin()->getExistingLangCodes($this);
  }

  /**
   * {@inheritdoc}
   */
  public function recalculateStatistics() {
    // Set translatable data from the current entity to calculate words.
    $this->decodeData();

    if (empty($this->unserializedData)) {
      $this->unserializedData = $this->getSourceData();
    }

    // Consider everything accepted when the job item is accepted.
    if ($this->isAccepted()) {
      $this->count_pending = 0;
      $this->count_translated = 0;
      $this->count_reviewed = 0;
      $this->count_accepted = count(array_filter(\Drupal::service('tmgmt.data')->flatten($this->unserializedData), array(\Drupal::service('tmgmt.data'), 'filterData')));
    }
    // Count the data item states.
    else {
      // Reset counter values.
      $this->count_pending = 0;
      $this->count_translated = 0;
      $this->count_reviewed = 0;
      $this->count_accepted = 0;
      $this->word_count = 0;
      $this->tags_count = 0;
      $this->count($this->unserializedData);
    }
  }

  /**
   * Sums up the counters for accepted, translated and pending items.
   *
   * @param array $item
   *   The current data item.
   */
  protected function count(&$item) {
    if (!empty($item['#text'])) {
      if (\Drupal::service('tmgmt.data')->filterData($item)) {

        // Count words of the data item.
        $this->word_count->value += \Drupal::service('tmgmt.data')->wordCount($item['#text']);

        // Count HTML tags of the data item.
        $this->tags_count->value += \Drupal::service('tmgmt.data')->tagsCount($item['#text']);

        // Set default states if no state is set.
        if (!isset($item['#status'])) {
          // Translation is present.
          if (!empty($item['#translation'])) {
            $item['#status'] = TMGMT_DATA_ITEM_STATE_TRANSLATED;
          }
          // No translation present.
          else {
            $item['#status'] = TMGMT_DATA_ITEM_STATE_PENDING;
          }
        }
        switch ($item['#status']) {
          case TMGMT_DATA_ITEM_STATE_REVIEWED:
            $this->count_reviewed->value++;
            break;

          case TMGMT_DATA_ITEM_STATE_TRANSLATED:
            $this->count_translated->value++;
            break;

          default:
            $this->count_pending->value++;
            break;
        }
      }
    }
    elseif (is_array($item)) {
      foreach (Element::children($item) as $key) {
        $this->count($item[$key]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    return new Language(array('id' => Language::LANGCODE_NOT_SPECIFIED));
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    if ($this->getJob()) {
      $tags = $this->getJob()->getEntityType()->getListCacheTags();
      if ($update) {
        $tags = Cache::mergeTags($tags, $this->getJob()
          ->getCacheTagsToInvalidate());
      }
      Cache::invalidateTags($tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStateIcon() {
    $state_definitions = static::getStateDefinitions();

    $state_definition = NULL;
    $translator_state = $this->getTranslatorState();
    if ($translator_state && isset($state_definitions[$translator_state]['icon'])) {
      $state_definition = $state_definitions[$translator_state];
    }
    elseif (isset($state_definitions[$this->getState()]['icon'])) {
      $state_definition = $state_definitions[$this->getState()];
    }

    if ($state_definition) {
      return [
        '#theme' => 'image',
        '#uri' => $state_definition['icon'],
        '#title' => $state_definition['label'],
        '#alt' => $state_definition['label'],
        '#width' => 16,
        '#height' => 16,
        '#weight' => $state_definition['weight'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getStates() {
    return array(
      static::STATE_ACTIVE => t('In progress'),
      static::STATE_REVIEW => t('Needs review'),
      static::STATE_ACCEPTED => t('Accepted'),
      static::STATE_ABORTED => t('Aborted'),
      static::STATE_INACTIVE => t('Inactive'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getStateDefinitions() {
    if (!empty(static::$stateDefinitions)) {
      return static::$stateDefinitions;
    }

    static::$stateDefinitions = [
      static::STATE_ACTIVE => [
        'label' => t('In progress'),
        'type' => 'state',
        'icon' => drupal_get_path('module', 'tmgmt') . '/icons/hourglass.svg',
        'weight' => 0,
        'show_job_filter' => TRUE,
      ],
      static::STATE_REVIEW => [
        'label' => t('Needs review'),
        'type' => 'state',
        'icon' => drupal_get_path('module', 'tmgmt') . '/icons/ready.svg',
        'weight' => 5,
        'show_job_filter' => TRUE,
      ],
      static::STATE_ACCEPTED => [
        'label' => t('Accepted'),
        'type' => 'state',
        'weight' => 10,
      ],
      static::STATE_ABORTED => [
        'label' => t('Aborted'),
        'type' => 'state',
        'weight' => 15,
      ],
      static::STATE_INACTIVE => [
        'label' => t('Inactive'),
        'type' => 'state',
        'weight' => 20,
      ],
    ];

    \Drupal::moduleHandler()->alter('tmgmt_job_item_state_definitions', static::$stateDefinitions);

    foreach (static::$stateDefinitions as $key => &$definition) {
      // Ensure that all state definitions have a type and label key set.
      assert(!empty($definition['type']) && !empty($definition['label']), "State definition $key is missing label or type.");
      // Set the default weight to 25, after the default states.
      $definition += [
        'weight' => 25,
      ];
    }
    uasort(static::$stateDefinitions, [SortArray::class, 'sortByWeightElement']);

    return static::$stateDefinitions;
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

}
