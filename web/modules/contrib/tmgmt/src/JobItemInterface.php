<?php

namespace Drupal\tmgmt;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for tmgmt_job_item entity.
 *
 * @ingroup tmgmt_job
 */
interface JobItemInterface extends ContentEntityInterface {

  /**
   * The translation job item is inactive.
   */
  const STATE_INACTIVE = 0;

  /**
   * The translation job item is active and waiting to be translated.
   *
   * A job item is marked as 'active' until every translatable piece of text in
   * the job item has been translated and cached on the job item entity.
   */
  const STATE_ACTIVE = 1;

  /**
   * The translation job item needs to be reviewed.
   *
   * A job item is marked as 'needs review' after every single piece of text in
   * the job item has been translated by the translation provider. After the
   * review procedure is finished the job item can be accepted and saved.
   */
  const STATE_REVIEW = 2;

  /**
   * The translation job item has been reviewed and accepted.
   *
   * After reviewing a job item it can be accepted by the reviewer. Once the user
   * has accepted the job item, the translated data will be propagated to the
   * source controller which will also take care of flagging the job item as
   * 'accepted' if the translated object could be saved successfully.
   */
  const STATE_ACCEPTED = 3;

  /**
   * The translation process of the job item is aborted.
   */
  const STATE_ABORTED = 4;

  /**
   * Returns the Job ID.
   *
   * @return int
   *   The job ID.
   */
  public function getJobId();

  /**
   * Returns the plugin.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPlugin();

  /**
   * Returns the item type.
   *
   * @return string
   *   The item type.
   */
  public function getItemType();

  /**
   * Returns the item ID.
   *
   * @return string
   *   The item ID.
   */
  public function getItemId();

  /**
   * Add a log message for this job item.
   *
   * @param string $message
   *   The message to store in the log. Keep $message translatable by not
   *   concatenating dynamic values into it! Variables in the message should be
   *   added by using placeholder strings alongside the variables argument to
   *   declare the value of the placeholders. See t() for documentation on how
   *   $message and $variables interact.
   * @param array $variables
   *   (Optional) An array of variables to replace in the message on display.
   * @param string $type
   *   (Optional) The type of the message. Can be one of 'status', 'error',
   *   'warning' or 'debug'. Messages of the type 'debug' will not get printed
   *   to the screen.
   *
   * @return string
   *    \Drupal\tmgmt\MessageInterface
   */
  public function addMessage($message, $variables = array(), $type = 'status');

  /**
   * Retrieves the label of the source object via the source controller.
   *
   * @return string
   *   The label of the source object.
   */
  public function getSourceLabel();

  /**
   * Retrieves the path to the source object via the source controller.
   *
   * @return \Drupal\Core\Url
   *   The URL object for the source object.
   */
  public function getSourceUrl();

  /**
   * Returns the user readable type of job item.
   */
  public function getSourceType();

  /**
   * Loads the job entity that this job item is attached to.
   *
   * @return \Drupal\tmgmt\JobInterface
   *   The job entity that this job item is attached to or NULL if there is
   *   no job.
   */
  public function getJob();

  /**
   * Returns the translator for this job item.
   *
   * @return \Drupal\tmgmt\TranslatorInterface
   *   The translator entity or NULL if there is none.
   */
  public function getTranslator();

  /**
   * Checks if the translator exists.
   *
   * @return bool
   *   TRUE if exists, FALSE otherwise.
   */
  public function hasTranslator();

  /**
   * Returns the translator plugin of the translator of this job item.
   *
   * @return \Drupal\tmgmt\TranslatorPluginInterface|null
   *   The translator plugin instance or NULL if there is none.
   */
  public function getTranslatorPlugin();

  /**
   * Attempts to abort the translation job item.
   *
   * Already accepted job items can not be aborted. Always use this method if
   * you want to abort a translation job item.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   If fails to abort the job item.
   */
  public function abortTranslation();

  /**
   * Array of the data to be translated.
   *
   * The structure is similar to the form API in the way that it is a possibly
   * nested array with the following properties whose presence indicate that the
   * current element is a text that might need to be translated.
   *
   * - #text: The text to be translated.
   * - #label: (Optional) The label that might be shown to the translator.
   * - #comment: (Optional) A comment with additional information.
   * - #translate: (Optional) If set to FALSE the text will not be translated.
   * - #translation: The translated data. Set by the translator plugin.
   * - #escape: (Optional) List of arrays with a required string key, keyed by
   *   the position key. Translators must use this list to prevent translation
   *   of these strings if possible.
   *
   * @todo: Move data item documentation to a new, separate api group.
   *
   * The key can be any alphanumeric character and '_'.
   *
   * @param array $key
   *   If present, only the subarray identified by key is returned.
   * @param int $index
   *   Optional index of an attribute below $key.
   *
   * @return array
   *   A structured data array.
   */
  public function getData($key = array(), $index = NULL);

  /**
   * Loads the structured source data array from the source.
   */
  public function getSourceData();

  /**
   * Returns an instance of the configured source plugin.
   *
   * @return \Drupal\tmgmt\SourcePluginInterface
   */
  public function getSourcePlugin();

  /**
   * Count of all pending data items.
   *
   * @return int
   *   Pending counts.
   */
  public function getCountPending();

  /**
   * Count of all translated data items.
   *
   * @return int
   *   Translated count.
   */
  public function getCountTranslated();

  /**
   * Count of all accepted data items.
   *
   * @return int
   *   Accepted count.
   */
  public function getCountAccepted();

  /**
   * Count of all accepted data items.
   *
   * @return int
   *   Accepted count
   */
  public function getCountReviewed();

  /**
   * Word count of all data items.
   *
   * @return int
   *   Word count
   */
  public function getWordCount();

  /**
   * Tags count of all data items.
   *
   * @return int
   *   Tags count
   */
  public function getTagsCount();

  /**
   * Sets the state of the job item to 'needs review'.
   *
   * @param string $message
   *   Message for the source to be reviewed.
   * @param array $variables
   *   (optional) An array of variables to replace in the message on display.
   * @param string $type
   *   (optional) Statically set to status.
   */
  public function needsReview($message = NULL, $variables = array(), $type = 'status');

  /**
   * Sets the state of the job item to 'accepted'.
   *
   * @param string $message
   *   Message for the source to be reviewed.
   * @param array $variables
   *   (optional) An array of variables to replace in the message on display.
   * @param string $type
   *   (optional) Statically set to status.
   */
  public function accepted($message = NULL, $variables = array(), $type = 'status');

  /**
   * Sets the state of the job item to 'active'.
   *
   * @param string $message
   *   Message for the source to be reviewed.
   * @param array $variables
   *   (optional) An array of variables to replace in the message on display.
   * @param string $type
   *   (optional) Statically set to status.
   */
  public function active($message = NULL, $variables = array(), $type = 'status');

  /**
   * Updates the state of the job item.
   *
   * @param string $state
   *   The new state of the job item. Has to be one of the job state constants.
   * @param string $message
   *   (Optional) The log message to be saved along with the state change.
   * @param array $variables
   *   (Optional) An array of variables to replace in the message on display.
   * @param string $type
   *   (optional) Statically set to status.
   *
   * @return int
   *   The updated state of the job item if it could be set.
   *
   * @see Job::addMessage()
   */
  public function setState($state, $message = NULL, $variables = array(), $type = 'debug');

  /**
   * Returns the state of the job item. Can be one of the job item state
   * constants.
   *
   * @return int
   *   The state of the job item.
   */
  public function getState();

  /**
   * Checks whether the passed value matches the current state.
   *
   * @param string $state
   *   The value to check the current state against.
   *
   * @return bool
   *   TRUE if the passed state matches the current state, FALSE otherwise.
   */
  public function isState($state);

  /**
   * Checks whether the state of this transaction is 'accepted'.
   *
   * @return bool
   *   TRUE if the state is 'accepted', FALSE otherwise.
   */
  public function isAccepted();

  /**
   * Checks whether the state of this transaction is 'active'.
   *
   * @return bool
   *   TRUE if the state is 'active', FALSE otherwise.
   */
  public function isActive();

  /**
   * Checks whether the state of this transaction is 'needs review'.
   *
   * @return bool
   *   TRUE if the state is 'needs review', FALSE otherwise.
   */
  public function isNeedsReview();

  /**
   * Checks whether the state of this transaction is 'aborted'.
   *
   * @return bool
   *   TRUE if the state is 'aborted', FALSE otherwise.
   */
  public function isAborted();

  /**
   * Checks whether the state of this transaction is 'inactive'.
   *
   * @return bool
   *   TRUE if the state is 'inactive', FALSE otherwise.
   */
  public function isInactive();

  /**
   * Reverts data item translation to the latest existing revision.
   *
   * @param array $key
   *   Data item key that should be reverted.
   *
   * @return bool
   *   Result of the revert action.
   */
  public function dataItemRevert(array $key);

  /**
   * Updates the values for a specific substructure in the data array.
   *
   * The values are either set or updated but never deleted.
   *
   * @param string|array $key
   *   Key pointing to the item the values should be applied.
   *   The key can be either be an array containing the keys of a nested array
   *   hierarchy path or a string with '][' or '|' as delimiter.
   * @param array $values
   *   Nested array of values to set.
   * @param bool $replace
   *   (optional) When TRUE, replaces the structure at the provided key instead
   *   of writing into it.
   */
  public function updateData($key, $values = array(), $replace = FALSE);

  /**
   * Resets job item data arrays.
   */
  public function resetData();

  /**
   * Adds translated data to a job item.
   *
   * This function calls for JobItem::addTranslatedDataRecursive() which
   * sets the status of each added data item to TMGMT_DATA_ITEM_STATE_TRANSLATED.
   *
   * Following rules apply while adding translated data:
   *
   * 1) Updated are only items that are changed. In case there is local
   * modification the translation is added as a revision with a message stating
   * this fact.
   *
   * 2) Merging happens at the data items level, so updating only those that are
   * changed. If a data item is in review/reject status and is being updated
   * with translation originating from remote the status is updated to
   * 'translated' no matter if it is changed or not.
   *
   * 3) Each time a data item is updated the previous translation becomes a
   * revision.
   *
   * If all data items are translated, the status of the job item is updated to
   * needs review.
   *
   * @todo
   * To update the job item status to needs review we could take advantage of
   * the JobItem::getCountPending() and JobItem::getCountTranslated().
   * The catch is, that this counter gets updated while saveing which not yet
   * hapened.
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
   *   Explicitly pass TMGMT_DATA_ITEM_STATE_TRANSLATED or
   *   TMGMT_DATA_ITEM_STATE_PRELIMINARY to set it to that value.
   *   Other statuses are not supported.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   If is given an unsupported status.
   */
  public function addTranslatedData(array $translation, $key = array(), $status = NULL);

  /**
   * Propagates the returned job item translations to the sources.
   *
   * @return bool
   *   TRUE if we were able to propagate the translated data and the item could
   *   be saved, FALSE otherwise.
   */
  public function acceptTranslation();

  /**
   * Returns all job item messages attached to this job item.
   *
   * @param array $conditions
   *   An array of conditions.
   *
   * @return array
   *   An array of translation job item messages.
   */
  public function getMessages($conditions = array());

  /**
   * Retrieves all siblings of this job item.
   *
   * @return array
   *   An array of job items that are the siblings of this job item.
   */
  public function getSiblings();

  /**
   * Returns all job item messages attached to this job item with timestamp
   * newer than $time.
   *
   * @param int $time
   *   (Optional) Messages need to have a newer timestamp than $time. Defaults
   *   to REQUEST_TIME.
   *
   * @return array
   *   An array of translation job item messages.
   */
  public function getMessagesSince($time = NULL);

  /**
   * Adds remote mapping entity to this job item.
   *
   * @param string $data_item_key
   *   Job data item key.
   * @param int $remote_identifier_1
   *   Array of remote identifiers. In case you need to save
   *   remote_identifier_2/3 set it into $mapping_data argument.
   * @param array $mapping_data
   *   Additional data to be added.
   *
   * @return int|bool
   *   Returns either the integer or boolean.
   *
   * @throws TMGMTException
   *   Throws an exception.
   */
  public function addRemoteMapping($data_item_key = NULL, $remote_identifier_1 = NULL, $mapping_data = array());

  /**
   * Gets remote mappings for current job item.
   *
   * @return array
   *   List of TMGMTRemote entities.
   */
  public function getRemoteMappings();

  /**
   * Gets language code of the job item source.
   *
   * @return string
   *   Language code.
   */
  public function getSourceLangCode();

  /**
   * Gets existing translation language codes of the job item source.
   *
   * @return array
   *   Array of language codes.
   */
  public function getExistingLangCodes();

  /**
   * Recalculate statistical word-data: pending, translated, reviewed, accepted.
   */
  public function recalculateStatistics();

  /**
   * Returns the current translator state.
   *
   * Translator states are expected to be exposed through
   * hook_tmgmt_job_item_state_definitions_alter().
   *
   * @return string|null
   *   The translator state or NULL if none is set.
   */
  public function getTranslatorState();

  /**
   * Sets the translator state.
   *
   * A translator state is only kept for a given job item state, if that changes
   * then the translator state is reset.
   *
   * @param string|null $translator_state
   *   Set the translator set, use NULL to reset.
   *
   * @return mixed
   */
  public function setTranslatorState($translator_state = NULL);

  /**
   * Returns a render array to display a job item state icon.
   *
   * @return array|null
   *   A render array for the icon or NULL if there is none for the current
   *   state.
   */
  public function getStateIcon();

  /**
   * Returns a labeled list of all available states.
   *
   * @return array
   *   A list of all available states.
   */
  public static function getStates();

}
