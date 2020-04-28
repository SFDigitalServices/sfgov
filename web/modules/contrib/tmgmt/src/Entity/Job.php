<?php

namespace Drupal\tmgmt\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\Translator\TranslatableResult;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Entity class for the tmgmt_job entity.
 *
 * @ContentEntityType(
 *   id = "tmgmt_job",
 *   label = @Translation("Translation Job"),
 *   module = "tmgmt",
 *   handlers = {
 *     "access" = "Drupal\tmgmt\Entity\Controller\JobAccessControlHandler",
 *     "form" = {
 *       "edit" = "Drupal\tmgmt\Form\JobForm",
 *       "abort" = "Drupal\tmgmt\Form\JobAbortForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "resubmit" = "Drupal\tmgmt\Form\JobResubmitForm",
 *       "continuous_add" = "Drupal\tmgmt\Form\ContinuousJobForm",
 *     },
 *     "list_builder" = "Drupal\tmgmt\Entity\ListBuilder\JobListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\tmgmt\Entity\ViewsData\JobViewsData",
 *   },
 *   base_table = "tmgmt_job",
 *   entity_keys = {
 *     "id" = "tjid",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/tmgmt/jobs/{tmgmt_job}",
 *     "abort-form" = "/admin/tmgmt/jobs/{tmgmt_job}/abort",
 *     "delete-form" = "/admin/tmgmt/jobs/{tmgmt_job}/delete",
 *     "resubmit-form" = "/admin/tmgmt/jobs/{tmgmt_job}/resubmit",
 *     "continuous-add-form" = "/admin/tmgmt/continuous_jobs/continuous_add",
 *   }
 * )
 *
 * @ingroup tmgmt_job
 */
class Job extends ContentEntityBase implements EntityOwnerInterface, JobInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['tjid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Job ID'))
      ->setDescription(t('The Job ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The node UUID.'))
      ->setReadOnly(TRUE);

    $fields['source_language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Source language code'))
      ->setDescription(t('The source language.'));

    $fields['target_language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Target language code'))
      ->setDescription(t('The target language.'));

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('The label of this job.'))
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
      ->setDescription(t('The user that is the job owner.'))
      ->setSettings(array(
        'target_type' => 'user',
      ))
      ->setDefaultValue(0);

    $fields['translator'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Provider'))
      ->setDescription(t('The selected provider'))
      ->setSettings(array(
        'target_type' => 'tmgmt_translator',
      ));

    $fields['settings'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Settings'))
      ->setDescription(t('Provider specific configuration and context information for this job.'))
      ->setDefaultValue(array());

    $fields['reference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reference'))
      ->setDescription(t('Remote reference of this job'))
      ->setDefaultValue('')
      ->setSettings(array(
        'max_length' => 255,
      ));
    $fields['state'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Job state'))
      ->setDescription(t('The job state.'))
      ->setSetting('allowed_values', Job::getStates())
      ->setDefaultValue(Job::STATE_UNPROCESSED);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the job was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the job was last edited.'));

    $fields['job_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Job type'))
      ->setDescription(t('Type of job entity, can be Normal or Continuous.'))
      ->setSetting('allowed_values', [static::TYPE_NORMAL, static::TYPE_CONTINUOUS])
      ->setDefaultValue(static::TYPE_NORMAL);

    $fields['continuous_settings'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Continuous settings'))
      ->setDescription(t('Continuous sources configuration.'))
      ->setDefaultValue(array());

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    // If either the source or target language is empty.
    if (empty($values['source_language']) || empty($values['target_language'])) {
      $languages = tmgmt_available_languages();
      if (empty($values['source_language'])) {
        $values['source_language'] = key($languages);
      }
      if (empty($values['target_language'])) {
        // Values might be an array, simplify it to a scalar langcode.
        $source_language = $values['source_language'];
        while (is_array($source_language)) {
          $source_language = reset($source_language);
        }

        // Make sure the source language is not available as target language,
        // use the next best value as the default.
        unset($languages[$source_language]);
        $values['target_language'] = key($languages);
      }
    }
  }

  /**
   * If TRUE, getData will just return those items that are not yet translated.
   *
   * @var bool
   */
  protected $filterTranslatedItems = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getTargetLanguage() {
    return $this->get('target_language')->language;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetLangcode() {
    return $this->get('target_language')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLanguage() {
    return $this->get('source_language')->language;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLangcode() {
    return $this->get('source_language')->value;
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
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getReference() {
    return $this->get('reference')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobType() {
    return $this->get('job_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getContinuousSettings() {
    return $this->get('continuous_settings')[0] ? $this->get('continuous_settings')[0]->getValue() : array();
  }

  /**
   * {@inheritdoc}
   */
  public function cloneAsUnprocessed() {
    $clone = $this->createDuplicate();
    $clone->uid->value = 0;
    $clone->reference->value = '';
    $clone->created->value = REQUEST_TIME;
    $clone->state->value = Job::STATE_UNPROCESSED;
    return $clone;
  }


  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    $entity_type_manager = \Drupal::entityTypeManager();

    // Since we are deleting one or multiple jobs here we also need to delete
    // the attached job items and messages.
    $tjiids = \Drupal::entityQuery('tmgmt_job_item')
      ->condition('tjid', array_keys($entities), 'IN')
      ->execute();
    if (!empty($tjiids)) {
      $job_items = $entity_type_manager->getStorage('tmgmt_job_item')->loadMultiple($tjiids);
      $entity_type_manager->getStorage('tmgmt_job_item')->delete($job_items);
    }

    $mids = \Drupal::entityQuery('tmgmt_message')
      ->condition('tjid', array_keys($entities), 'IN')
      ->execute();
    if (!empty($mids)) {
      $messages = $entity_type_manager->getStorage('tmgmt_message')->loadMultiple($mids);
      $entity_type_manager->getStorage('tmgmt_message')->delete($messages);
    }

    $trids = \Drupal::entityQuery('tmgmt_remote')
      ->condition('tjid', array_keys($entities), 'IN')
      ->execute();
    if (!empty($trids)) {
      $remotes = $entity_type_manager->getStorage('tmgmt_remote')->loadMultiple($trids);
      $entity_type_manager->getStorage('tmgmt_remote')->delete($remotes);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    // In some cases we might have a user-defined label.
    if (!empty($this->get('label')->value)) {
      return $this->get('label')->value;
    }

    $items = $this->getItems();
    $count = count($items);
    if ($count > 0) {
      $source_label = reset($items)->getSourceLabel();
      $t_args = array('@title' => $source_label, '@more' => $count - 1);
      $label = \Drupal::translation()->formatPlural($count, '@title', '@title and @more more', $t_args);

      // If the label length exceeds maximum allowed then cut off exceeding
      // characters from the title and use it to recreate the label.
      if (strlen($label) > Job::LABEL_MAX_LENGTH) {
        $max_length = strlen($source_label) - (strlen($label) - Job::LABEL_MAX_LENGTH);
        $source_label = Unicode::truncate($source_label, $max_length, TRUE);
        $t_args['@title'] = $source_label;
        $label = \Drupal::translation()->formatPlural($count, '@title', '@title and @more more', $t_args);
      }
    }
    else {
      $source = $this->getSourceLanguage() ? $this->getSourceLanguage()->getName() : '?';
      $target = $this->getTargetLanguage() ? $this->getTargetLanguage()->getName() : '?';
      $label = t('From @source to @target', array('@source' => $source, '@target' => $target));
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function addItem($plugin, $item_type, $item_id) {

    $transaction = \Drupal::database()->startTransaction();
    $is_new = FALSE;

    if ($this->isNew()) {
      $this->save();
      $is_new = TRUE;
    }

    $item = tmgmt_job_item_create($plugin, $item_type, $item_id, array('tjid' => $this->id()));
    $item->save();

    if ($item->getWordCount() == 0) {
      $transaction->rollback();

      // In case we got word count 0 for the first job item, NULL tjid so that
      // if there is another addItem() call the rolled back job object will get
      // persisted.
      if ($is_new) {
        $this->tjid = NULL;
      }

      throw new TMGMTException('Job item @label (@type) has no translatable content.',
        array('@label' => $item->label(), '@type' => $item->getSourceType()));
    }

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function addExistingItem(JobItemInterface $item) {
    $item->tjid = $this->id();
    $item->save();

  }

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $variables = array(), $type = 'status') {
    // Save the job if it hasn't yet been saved.
    if (!$this->isNew() || $this->save()) {
      $message = tmgmt_message_create($message, $variables, array('tjid' => $this->id(), 'type' => $type));
      if ($message->save()) {
        return $message;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems($conditions = array()) {
    $items = [];
    $query = \Drupal::entityQuery('tmgmt_job_item')
      ->condition('tjid', $this->id());
    foreach ($conditions as $key => $condition) {
      if (is_array($condition)) {
        $operator = isset($condition['operator']) ? $condition['operator'] : '=';
        $query->condition($key, $condition['value'], $operator);
      }
      else {
        $query->condition($key, $condition);
      }
    }
    $query->sort('tjiid', 'ASC');
    $results = $query->execute();
    if (!empty($results)) {
      $items = \Drupal::entityTypeManager()->getStorage('tmgmt_job_item')->loadMultiple($results);
      if ($this->filterTranslatedItems) {
        $items = array_filter($items, function (JobItemInterface $item) {
          return $item->getCountPending() > 0;
        });
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getMostRecentItem($plugin, $item_type, $item_id) {
    $query = \Drupal::entityQuery('tmgmt_job_item')
      ->condition('tjid', $this->id())
      ->condition('plugin', $plugin)
      ->condition('item_type', $item_type)
      ->condition('item_id', $item_id)
      ->sort('tjiid', 'DESC')
      ->range(0, 1);
    $result = $query->execute();
    if (!empty($result)) {
      return JobItem::load(reset($result));
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages($conditions = array()) {
    $query = \Drupal::entityQuery('tmgmt_message')
      ->condition('tjid', $this->id());
    foreach ($conditions as $key => $condition) {
      if (is_array($condition)) {
        $operator = isset($condition['operator']) ? $condition['operator'] : '=';
        $query->condition($key, $condition['value'], $operator);
      }
      else {
        $query->condition($key, $condition);
      }
    }
    $query->sort('created', 'ASC');
    $query->sort('mid', 'ASC');
    $results = $query->execute();
    if (!empty($results)) {
      return Message::loadMultiple($results);
    }
    return array();
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
  public function getRemoteSourceLanguage() {
    return $this->getTranslator()->mapToRemoteLanguage($this->getSourceLangcode());
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteTargetLanguage() {
    return $this->getTranslator()->mapToRemoteLanguage($this->getTargetLangcode());
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name) {
    if (isset($this->settings->$name)) {
      return $this->settings->$name;
    }
    // The translator might provide default settings.
    if ($this->hasTranslator()) {
      if (($setting = $this->getTranslator()->getSetting($name)) !== NULL) {
        return $setting;
      }
      $defaults = $this->getTranslatorPlugin()->defaultSettings();
      if (isset($defaults[$name])) {
        return $defaults[$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatorId() {
    return $this->get('translator')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatorLabel() {
    if ($this->hasTranslator()) {
      return $this->getTranslator()->label();
    }
    if ($this->getTranslatorId() == NULL) {
      return t('(Undefined)');
    }
    return t('(Missing)');
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslator() {
    if ($this->hasTranslator()) {
      return $this->translator->entity;
    }
    else if (!$this->translator->entity) {
      throw new TMGMTException('The job has no provider assigned.');
    }
    else if (!$this->translator->entity->hasPlugin()) {
      throw new TMGMTException('The translator assigned to this job is missing the plugin.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslator() {
    return $this->translator->entity && $this->translator->target_id && $this->translator->entity->hasPlugin();
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
  public function setState($state, $message = NULL, $variables = array(), $type = 'debug') {
    // Return TRUE if the state could be set. Return FALSE otherwise.
    if (array_key_exists($state, Job::getStates())) {
      $this->state = $state;
      $this->save();
      // If a message is attached to this state change add it now.
      if (!empty($message)) {
        $this->addMessage($message, $variables, $type);
      }
    }
    return $this->getState();
  }

  /**
   * {@inheritdoc}
   */
  public function isState($state) {
    return $this->getState() == $state;
  }

  /**
   * Checks whether the user described by $account is the author of this job.
   *
   * @param AccountInterface $account
   *   (Optional) A user object. Defaults to the currently logged in user.
   *
   * @return bool
   *   TRUE if the passed account is the job owner.
   */
  public function isAuthor(AccountInterface $account = NULL) {
    $account = isset($account) ? $account : \Drupal::currentUser();
    return $this->getOwnerId() == $account->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isUnprocessed() {
    return $this->isState(Job::STATE_UNPROCESSED);
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
  public function isActive() {
    return $this->isState(static::STATE_ACTIVE);
  }

  /**
   * {@inheritdoc}
   */
  public function isRejected() {
    return $this->isState(static::STATE_REJECTED);
  }

  /**
   * {@inheritdoc}
   */
  public function isFinished() {
    return $this->isState(static::STATE_FINISHED);
  }

  /**
   * {@inheritdoc}
   */
  public function isContinuousActive() {
    return $this->isState(static::STATE_CONTINUOUS);
  }

  /**
   * {@inheritdoc}
   */
  public function isContinuousInactive() {
    return $this->isState(static::STATE_CONTINUOUS_INACTIVE);
  }

  /**
   * {@inheritdoc}
   */
  public function canRequestTranslation() {
    if ($translator = $this->getTranslator()) {
      return $translator->checkTranslatable($this);
    }
    return TranslatableResult::no(t('Translation cant be requested.'));
  }

  /**
   * {@inheritdoc}
   */
  public function isAbortable() {
    // Only non-submitted translation jobs can be aborted.
    if ($this->isContinuous()) {
      return FALSE;
    }
    else {
      return $this->isActive();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isSubmittable() {
    if ($this->isContinuous()) {
      return FALSE;
    }
    else {
      return $this->isUnprocessed() || $this->isRejected();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return !$this->isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function isContinuous() {
    return $this->getJobType() == static::TYPE_CONTINUOUS;
  }

  /**
   * {@inheritdoc}
   */
  public function submitted($message = NULL, $variables = array(), $type = 'status') {
    if (!isset($message)) {
      $message = 'The translation job has been submitted.';
    }
    $this->setState(static::STATE_ACTIVE, $message, $variables, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function finished($message = NULL, $variables = array(), $type = 'status') {
    if (!isset($message)) {
      $message = 'The translation job has been finished.';
    }
    return $this->setState(static::STATE_FINISHED, $message, $variables, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function aborted($message = NULL, $variables = array(), $type = 'status') {
    if (!isset($message)) {
      $message = 'The translation job has been aborted.';
    }
    /** @var \Drupal\tmgmt\JobItemInterface $item */
    foreach ($this->getItems() as $item) {
      $item->setState(JobItem::STATE_ABORTED);
    }
    return $this->setState(static::STATE_ABORTED, $message, $variables, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function rejected($message = NULL, $variables = array(), $type = 'error') {
    if (!isset($message)) {
      $message = 'The translation job has been rejected by the translation provider.';
    }
    return $this->setState(static::STATE_REJECTED, $message, $variables, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation() {
    if (!$this->canRequestTranslation()->getSuccess()) {
      return FALSE;
    }
    if (!$this->isContinuous()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }

    // Call the hook before requesting the translation.
    \Drupal::moduleHandler()->invokeAll('tmgmt_job_before_request_translation', [$this->getItems()]);

    // We do not want to translate the items that are already translated.
    $this->filterTranslatedItems = TRUE;

    // We don't know if the translator plugin already processed our
    // translation request after this point. That means that the plugin has to
    // set the 'submitted', 'needs review', etc. states on its own.
    if (!empty($this->getItems())) {
      $this->getTranslatorPlugin()->requestTranslation($this);
    }
    else {
      $this->submitted();
    }

    // Reset it again so getData returns again all the values.
    $this->filterTranslatedItems = FALSE;

    // Call the hook after requesting the translation.
    \Drupal::moduleHandler()->invokeAll('tmgmt_job_after_request_translation', [$this->getItems()]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if ($this->isContinuous() && !$this->isContinuousInactive() && !$this->isAborted()) {
      $this->state = Job::STATE_CONTINUOUS;
    }
    // Activate job item if the previous job state was not active.
    if ($this->isActive() && !$this->original->isActive()) {
      foreach ($this->getItems() as $item) {
        // The job was submitted, activate any inactive job item.
        if ($item->isInactive()) {
          $item->setState(JobItemInterface::STATE_ACTIVE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation() {
    if (!$this->isAbortable() || !$plugin = $this->getTranslatorPlugin()) {
      return FALSE;
    }
    // We don't know if the translator plugin was able to abort the translation
    // job after this point. That means that the plugin has to set the
    // 'aborted' state on its own.
    return $plugin->abortTranslation($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatorPlugin() {
    return $this->getTranslator()->getPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key = array(), $index = NULL) {
    $data = array();
    if (!empty($key)) {
      $tjiid = array_shift($key);
      $item = JobItem::load($tjiid);
      if ($item) {
        $data[$tjiid] = $item->getData($key, $index);
        // If not set, use the job item label as the data label.
        if (!isset($data[$tjiid]['#label'])) {
          $data[$tjiid]['#label'] = $item->getSourceLabel();
        }
      }
    }
    else {
      foreach ($this->getItems() as $tjiid => $item) {
        $data[$tjiid] = $item->getData();
        // If not set, use the job item label as the data label.
        if (!isset($data[$tjiid]['#label'])) {
          $data[$tjiid]['#label'] = $item->getSourceLabel();
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountPending() {
    return tmgmt_job_statistic($this, 'count_pending');
  }

  /**
   * {@inheritdoc}
   */
  public function getCountTranslated() {
    return tmgmt_job_statistic($this, 'count_translated');
  }

  /**
   * {@inheritdoc}
   */
  public function getCountAccepted() {
    return tmgmt_job_statistic($this, 'count_accepted');
  }

  /**
   * {@inheritdoc}
   */
  public function getCountReviewed() {
    return tmgmt_job_statistic($this, 'count_reviewed');
  }

  /**
   * {@inheritdoc}
   */
  public function getWordCount() {
    return tmgmt_job_statistic($this, 'word_count');
  }

  /**
   * {@inheritdoc}
   */
  public function getTagsCount() {
    return tmgmt_job_statistic($this, 'tags_count');
  }

  /**
   * {@inheritdoc}
   */
  public function addTranslatedData(array $data, $key = NULL, $status = NULL) {
    $key = \Drupal::service('tmgmt.data')->ensureArrayKey($key);
    $items = $this->getItems();
    // If there is a key, get the specific item and forward the call.
    if (!empty($key)) {
      $item_id = array_shift($key);
      if (isset($items[$item_id])) {
        $items[$item_id]->addTranslatedData($data, $key, $status);
      }
    }
    else {
      foreach ($data as $key => $value) {
        if (isset($items[$key])) {
          $items[$key]->addTranslatedData($value, [], $status);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function acceptTranslation() {
    foreach ($this->getItems() as $item) {
      $item->acceptTranslation();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteMappings() {
    $trids = \Drupal::entityQuery('tmgmt_remote')
      ->condition('tjid', $this->id())
      ->execute();

    if (!empty($trids)) {
      return RemoteMapping::loadMultiple($trids);
    }

    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestions(array $conditions = array()) {
    $suggestions = \Drupal::moduleHandler()->invokeAll('tmgmt_source_suggestions', array($this->getItems($conditions), $this));

    // EachJob needs a job id to be able to count the words, because the
    // source-language is stored in the job and not the item.
    foreach ($suggestions as &$suggestion) {
      $jobItem = $suggestion['job_item'];
      $jobItem->tjid = $this->id();
      $jobItem->recalculateStatistics();
    }
    return $suggestions;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanSuggestionsList(array &$suggestions) {
    foreach ($suggestions as $k => $suggestion) {
      if (is_array($suggestion) && isset($suggestion['job_item']) && ($suggestion['job_item'] instanceof JobItem)) {
        $jobItem = $suggestion['job_item'];

        // Items with no words to translate should not be presented.
        if ($jobItem->getWordCount() <= 0) {
          unset($suggestions[$k]);
          continue;
        }

        // Check if there already exists a translation job for this item in the
        // current language.
        $items = tmgmt_job_item_load_all_latest($jobItem->getPlugin(), $jobItem->getItemType(), $jobItem->getItemId(), $this->getSourceLangcode());
        if (isset($items[$this->getTargetLangcode()])) {
          unset($suggestions[$k]);
          continue;
        }

        // If the item is part of the current job, no matter which language,
        // remove it.
        foreach ($items as $item) {
          if ($item->getJobId() == $this->id()) {
            unset($suggestions[$k]);
            continue;
          }
        }
      } else {
        unset($suggestions[$k]);
        continue;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    // Translation jobs themself can not be translated.
    return FALSE;
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
  public static function getStates() {
    return array(
      static::STATE_UNPROCESSED => t('Unprocessed'),
      static::STATE_ACTIVE => t('Active'),
      static::STATE_REJECTED => t('Rejected'),
      static::STATE_ABORTED => t('Aborted'),
      static::STATE_FINISHED => t('Finished'),
      static::STATE_CONTINUOUS => t('Continuous'),
      static::STATE_CONTINUOUS_INACTIVE => t('Continuous Inactive'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConflictingItemIds() {
    $conflicting_item_ids = array();
    foreach ($this->getItems() as $item) {
      // Count existing job items that are have the same languages, same source,
      // are active or in review and are not the job item that we are checking.
      $existing_items_count = \Drupal::entityQuery('tmgmt_job_item')
        ->condition('state', [JobItemInterface::STATE_ACTIVE, JobItemInterface::STATE_REVIEW], 'IN')
        ->condition('plugin', $item->getPlugin())
        ->condition('item_type', $item->getItemType())
        ->condition('item_id', $item->getItemId())
        ->condition('tjiid', $item->id(), '<>')
        ->condition('tjid.entity.source_language', $this->getSourceLangcode())
        ->condition('tjid.entity.target_language', $this->getTargetLangcode())
        ->count()
        ->execute();

      // If there are any, this is a conflicting job item.
      if ($existing_items_count) {
        $conflicting_item_ids[] = $item->id();
      }
    }
    return $conflicting_item_ids;
  }

}
