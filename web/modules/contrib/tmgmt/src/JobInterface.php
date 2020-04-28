<?php

namespace Drupal\tmgmt;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for tmgmt_job entity.
 *
 * @ingroup tmgmt_job
 */
interface JobInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * A new translation job.
   *
   * In the default user interface, jobs with this state are so called cart jobs.
   * Each user gets his cart jobs listed in a block and can check them out.
   */
  const STATE_UNPROCESSED = 0;

  /**
   * A translation job that has been submitted to the translator.
   *
   * Translator plugins are responsible for setting this state in their
   * implementation of
   * TranslatorPluginControllerInterface::requestTranslation().
   */
  const STATE_ACTIVE = 1;

  /**
   * A translation job that has been rejected by the translator.
   *
   * The translator plugin can use this state if the job has been actively
   * rejected. However, this should be avoided by doing the necessary checks
   * in the checkTranslatable() method and in the job configuration settings.
   *
   * A rejected job can be re-submitted.
   */
  const STATE_REJECTED = 2;

  /**
   * The translation job has been aborted.
   *
   * A job can be aborted at any time. If he is currently in the submitted state
   * the translator plugin is asked if this translation can be aborted and needs
   * to confirm it by returning TRUE in abortTranslation().
   */
  const STATE_ABORTED = 4;

  /**
   * The translation job has been finished.
   *
   * A job is marked as 'finished' after every single attached job item has been
   * reviewed, accepted and saved.
   */
  const STATE_FINISHED = 5;

  /**
   * A continuous translation job.
   *
   * A default state for all continuous jobs.
   */
  const STATE_CONTINUOUS = 6;

  /**
   * A continuous translation job has been inactivated.
   *
   * Inactive state for continuous translation jobs.
   */
  const STATE_CONTINUOUS_INACTIVE = 7;

  /**
   * Maximum length of a job or job item label.
   */
  const LABEL_MAX_LENGTH = 128;

  /**
   * Translation job of type Normal.
   */
  const TYPE_NORMAL = 'normal';

  /**
   * Translation job of type Continuous.
   */
  const TYPE_CONTINUOUS = 'continuous';

  /**
   * Returns the target language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The target language.
   */
  public function getTargetLanguage();

  /**
   * Returns the target language code.
   *
   * @return string
   *   The target language code
   */
  public function getTargetLangcode();

  /**
   * Returns the source language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The source language.
   */
  public function getSourceLanguage();

  /**
   * Returns the source language code.
   *
   * @return string
   *   The source language code
   */
  public function getSourceLangcode();

  /**
   * Returns the created time.
   *
   * @return int
   *   The time when the job was last changed.
   */
  public function getChangedTime();

  /**
   * Returns the created time.
   *
   * @return int
   *   The time when the job was last changed.
   */
  public function getCreatedTime();

  /**
   * Returns the reference.
   *
   * @return string
   *   The reference set by the translator.
   */
  public function getReference();

  /**
   * Returns the job type.
   *
   * @return string
   *   The job type.
   */
  public function getJobType();

  /**
   * Returns continuous settings.
   *
   * @return array
   *   Continuous settings.
   */
  public function getContinuousSettings();

  /**
   * Clones job as unprocessed.
   */
  public function cloneAsUnprocessed();

  /**
   * Adds an item to the translation job.
   *
   * @param string $plugin
   *   The plugin name.
   * @param string $item_type
   *   The source item type.
   * @param string $item_id
   *   The source item id.
   *
   * @return \Drupal\tmgmt\JobItemInterface
   *   The job item that was added to the job or FALSE if it couldn't be saved.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   On zero item word count.
   */
  public function addItem($plugin, $item_type, $item_id);

  /**
   * Add a givenJobItem to this job.
   *
   * @param \Drupal\tmgmt\JobItemInterface $item
   *   The job item to add.
   */
  public function addExistingItem(JobItemInterface $item);

  /**
   * Add a log message for this job.
   *
   * @param string $message
   *   The message to store in the log. Keep $message translatable by not
   *   concatenating dynamic values into it! Variables in the message should be
   *   added by using placeholder strings alongside the variables argument to
   *   declare the value of the placeholders. See t() for documentation on how
   *   $message and $variables interact.
   * @param string[] $variables
   *   (Optional) An array of variables to replace in the message on display.
   * @param string $type
   *   (Optional) The type of the message. Can be one of 'status', 'error',
   *   'warning' or 'debug'. Messages of the type 'debug' will not get printed
   *   to the screen.
   */
  public function addMessage($message, $variables = array(), $type = 'status');

  /**
   * Returns all job items attached to this job.
   *
   * @param array $conditions
   *   Additional conditions.
   *
   * @return \Drupal\tmgmt\JobItemInterface[]
   *   An array of translation job items.
   */
  public function getItems($conditions = array());

  /**
   * Returns most recent job item attached to this job.
   *
   * @param string $plugin
   *   The plugin name.
   * @param string $item_type
   *   Source item type.
   * @param string $item_id
   *   Source item ID.
   *
   * @return \Drupal\tmgmt\JobItemInterface|null
   *   The most recent job item that matches that source or NULL if none
   *   exists.
   */
  public function getMostRecentItem($plugin, $item_type, $item_id);

  /**
   * Returns all job messages attached to this job.
   *
   * @param array $conditions
   *   Additional conditions.
   *
   * @return \Drupal\tmgmt\MessageInterface[]
   *   An array of translation job messages.
   */
  public function getMessages($conditions = array());

  /**
   * Returns all job messages attached to this job.
   *
   * It returns them with timestamp newer than $time.
   *
   * @param int $time
   *   (Optional) Messages need to have a newer timestamp than $time. Defaults
   *   to REQUEST_TIME.
   *
   * @return \Drupal\tmgmt\MessageInterface[]
   *   An array of translation job messages.
   */
  public function getMessagesSince($time = NULL);

  /**
   * Returns remote source language code.
   *
   * Maps the source langcode of the job from local to remote.
   *
   * @return string
   *   Remote language code.
   *
   * @ingroup tmgmt_remote_languages_mapping
   */
  public function getRemoteSourceLanguage();

  /**
   * Returns remote target language code.
   *
   * Maps the target langcode of the job from local to remote.
   *
   * @return string
   *   Remote language code.
   *
   * @ingroup tmgmt_remote_languages_mapping
   */
  public function getRemoteTargetLanguage();

  /**
   * Retrieves a setting value from the job settings.
   *
   * Pulls the default values (if defined) from the plugin controller.
   *
   * @param string $name
   *   The name of the setting.
   *
   * @return string
   *   The setting value or $default if the setting value is not set. Returns
   *   NULL if the setting does not exist at all.
   */
  public function getSetting($name);

  /**
   * Returns the translator ID for this job.
   *
   * @return int|null
   *   The translator ID or NULL if there is none.
   */
  public function getTranslatorId();

  /**
   * Returns the label of the translator for this job.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The label of the translator, "(Missing)" in case the translator has
   *   been deleted or "(Undefined)" in case the translator is not set.
   */
  public function getTranslatorLabel();

  /**
   * Returns the translator for this job.
   *
   * @return \Drupal\tmgmt\Entity\Translator
   *   The translator entity.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   Throws an exception when there is no translator assigned or when the
   *   translator is missing the plugin.
   */
  public function getTranslator();

  /**
   * Checks if the translator and the plugin exist.
   *
   * @return bool
   *   TRUE if exists, FALSE otherwise.
   */
  public function hasTranslator();

  /**
   * Returns the state of the job. Can be one of the job state constants.
   *
   * @return int
   *   The state of the job or NULL if it hasn't been set yet.
   */
  public function getState();

  /**
   * Updates the state of the job.
   *
   * @param int $state
   *   The new state of the job. Has to be one of the job state constants.
   * @param string $message
   *   (optional) The log message to be saved along with the state change.
   * @param array $variables
   *   (optional) An array of variables to replace in the message on display.
   * @param string $type
   *   (optional) The message type.
   *
   * @return int
   *   The updated state of the job if it could be set.
   *
   * @see Job::addMessage()
   */
  public function setState($state, $message = NULL, $variables = array(), $type = 'debug');

  /**
   * Checks whether the passed value matches the current state.
   *
   * @param int $state
   *   The value to check the current state against.
   *
   * @return bool
   *   TRUE if the passed state matches the current state, FALSE otherwise.
   */
  public function isState($state);

  /**
   * Checks whether the user described by $account is the author of this job.
   *
   * @param AccountInterface $account
   *   (Optional) A user object. Defaults to the currently logged in user.
   *
   * @return bool
   *   TRUE if the passed account is the job owner.
   */
  public function isAuthor(AccountInterface $account = NULL);

  /**
   * Returns whether the state of this job is 'unprocessed'.
   *
   * @return bool
   *   TRUE if the state is 'unprocessed', FALSE otherwise.
   */
  public function isUnprocessed();

  /**
   * Returns whether the state of this job is 'aborted'.
   *
   * @return bool
   *   TRUE if the state is 'aborted', FALSE otherwise.
   */
  public function isAborted();

  /**
   * Returns whether the state of this job is 'active'.
   *
   * @return bool
   *   TRUE if the state is 'active', FALSE otherwise.
   */
  public function isActive();

  /**
   * Returns whether the state of this job is 'rejected'.
   *
   * @return bool
   *   TRUE if the state is 'rejected', FALSE otherwise.
   */
  public function isRejected();

  /**
   * Returns whether the state of this job is 'finished'.
   *
   * @return bool
   *   TRUE if the state is 'finished', FALSE otherwise.
   */
  public function isFinished();

  /**
   * Returns whether the state of this job is 'continuous'.
   *
   * @return bool
   *   TRUE if the state is 'continuous', FALSE otherwise.
   */
  public function isContinuousActive();

  /**
   * Returns whether the state of this jon is 'continuous_inactive'.
   *
   * @return bool
   *   TRUE if the state is 'continuous_inactive', FALSE otherwise.
   */
  public function isContinuousInactive();

  /**
   * Checks whether a job is translatable.
   *
   * @return \Drupal\tmgmt\Translator\TranslatableResult
   *   Whether the job can be translated or not.
   */
  public function canRequestTranslation();

  /**
   * Checks whether a job is abortable.
   *
   * @return bool
   *   TRUE if the job can be aborted, FALSE otherwise.
   */
  public function isAbortable();

  /**
   * Checks whether a job is submittable.
   *
   * @return bool
   *   TRUE if the job can be submitted, FALSE otherwise.
   */
  public function isSubmittable();

  /**
   * Checks whether a job is deletable.
   *
   * @return bool
   *   TRUE if the job can be deleted, FALSE otherwise.
   */
  public function isDeletable();

  /**
   * Checks whether a job type is continuous.
   *
   * @return bool
   *   TRUE if the job is continuous, FALSE otherwise.
   */
  public function isContinuous();

  /**
   * Set the state of the job to 'submitted'.
   *
   * @param string $message
   *   (optional) The log message to be saved along with the state change.
   * @param array $variables
   *   (optional) An array of variables to replace in the message on display.
   * @param string $type
   *   (optional) The message type.
   *
   * @return \Drupal\tmgmt\JobInterface
   *   The job entity.
   *
   * @see static::addMessage()
   */
  public function submitted($message = NULL, $variables = array(), $type = 'status');

  /**
   * Set the state of the job to 'finished'.
   *
   * @param string $message
   *   The log message to be saved along with the state change.
   * @param array $variables
   *   (Optional) An array of variables to replace in the message on display.
   * @param string $type
   *    Statically set to status.
   *
   * @return \Drupal\tmgmt\JobInterface
   *   The job entity.
   *
   * @see Job::addMessage()
   */
  public function finished($message = NULL, $variables = array(), $type = 'status');

  /**
   * Sets the state of the job to 'aborted'.
   *
   * @param string $message
   *   The log message to be saved along with the state change.
   * @param array $variables
   *   (Optional) An array of variables to replace in the message on display.
   * @param string $type
   *    Statically set to status.
   *
   *   Use Job::abortTranslation() to abort a translation.
   *
   * @return \Drupal\tmgmt\JobInterface
   *   The job entity.
   *
   * @see Job::addMessage()
   */
  public function aborted($message = NULL, $variables = array(), $type = 'status');

  /**
   * Sets the state of the job to 'rejected'.
   *
   * @param string $message
   *   The log message to be saved along with the state change.
   * @param array $variables
   *   (Optional) An array of variables to replace in the message on display.
   * @param string $type
   *    Statically set to error.
   *
   * @return \Drupal\tmgmt\JobInterface
   *   The job entity.
   *
   * @see Job::addMessage()
   */
  public function rejected($message = NULL, $variables = array(), $type = 'error');

  /**
   * Request the translation of a job from the translator.
   *
   * @return int
   *   The updated job status.
   */
  public function requestTranslation();

  /**
   * Attempts to abort the translation job.
   *
   * Already accepted jobs can not be aborted, submitted jobs only if supported
   * by the translator plugin. Always use this method if you want to abort a
   * translation job.
   *
   * @return bool
   *   TRUE if the translation job was aborted, FALSE otherwise.
   */
  public function abortTranslation();

  /**
   * Returns the translator plugin of the translator of this job.
   *
   * @return \Drupal\tmgmt\TranslatorPluginInterface
   *   The translator plugin instance.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   Throws an exception when there is no translator assigned or when the
   *   translator is missing the plugin.
   */
  public function getTranslatorPlugin();

  /**
   * Returns the source data of all job items.
   *
   * @param array $key
   *   If present, only the subarray identified by key is returned.
   * @param int $index
   *   Optional index of an attribute below $key.
   *
   * @return array
   *   A nested array with the source data where the most upper key is the job
   *   item id.
   */
  public function getData($key = array(), $index = NULL);

  /**
   * Sums up all pending counts of this jobs job items.
   *
   * @return int
   *   The sum of all pending counts
   */
  public function getCountPending();

  /**
   * Sums up all translated counts of this jobs job items.
   *
   * @return int
   *   The sum of all translated counts
   */
  public function getCountTranslated();

  /**
   * Sums up all accepted counts of this jobs job items.
   *
   * @return int
   *   The sum of all accepted data items.
   */
  public function getCountAccepted();

  /**
   * Sums up all reviewed counts of this jobs job items.
   *
   * @return int
   *   The sum of all reviewed data items.
   */
  public function getCountReviewed();

  /**
   * Sums up all word counts of this jobs job items.
   *
   * @return int
   *   The total word count of this job.
   */
  public function getWordCount();

  /**
   * Sums up all HTML tags counts of this jobs job items.
   *
   * @return int
   *   The total tags count of this job.
   */
  public function getTagsCount();

  /**
   * Store translated data back into the items.
   *
   * @param array $data
   *   Partially or complete translated data, the most upper key needs to be
   *   the translation job item id.
   * @param array|string $key
   *   (Optional) Either a flattened key (a 'key1][key2][key3' string) or a
   *   nested one, e.g. array('key1', 'key2', 'key2'). Defaults to an empty
   *   array which means that it will replace the whole translated data array.
   *   The most upper key entry needs to be the job id (tjiid).
   * @param int|null $status
   *   (Optional) The data item status that will be set. Defaults to NULL,
   *   which means that it will be set to translated unless it was previously
   *   set to preliminary, then it will keep that state.
   *   Explicitly pass TMGMT_DATA_ITEM_STATE_TRANSLATED or
   *   TMGMT_DATA_ITEM_STATE_PRELIMINARY to set it to that value.
   *   Other statuses are not supported.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   If is given an unsupported status.
   */
  public function addTranslatedData(array $data, $key = NULL, $status = NULL);

  /**
   * Propagates the returned job item translations to the sources.
   *
   * @return bool
   *   TRUE if we were able to propagate the translated data, FALSE otherwise.
   */
  public function acceptTranslation();

  /**
   * Gets remote mappings for current job.
   *
   * @return array
   *   List of TMGMTRemote entities.
   */
  public function getRemoteMappings();

  /**
   * Invoke the hook 'hook_tmgmt_source_suggestions' to get all suggestions.
   *
   * @param array $conditions
   *   Conditions to pass only some and not all items to the hook.
   *
   * @return array
   *   An array with all additional translation suggestions.
   *   - job_item: AJobItem instance.
   *   - referenced: A string which indicates where this suggestion comes from.
   *   - from_job: The mainJob-ID which suggests this translation.
   */
  public function getSuggestions(array $conditions = array());

  /**
   * Removes all suggestions from the given list which should not be processed.
   *
   * This function removes all suggestions from the given list which are already
   * assigned to a translation job or which should not be processed because
   * there are no words, no translation is needed, ...
   *
   * @param array &$suggestions
   *   Associative array of translation suggestions. It must contain at least:
   *   - tmgmt_job: An instance of aJobItem.
   */
  public function cleanSuggestionsList(array &$suggestions);

  /**
   * Returns a labeled list of all available states.
   *
   * @return array
   *   A list of all available states.
   */
  public static function getStates();

  /**
   * Returns conflicting job item IDs.
   *
   * Conflicting job items are those that already have an identical item
   * in another job that is not yet finished.
   *
   * @return int[]
   *   List of conflicting job item IDs.
   */
  public function getConflictingItemIds();

}
