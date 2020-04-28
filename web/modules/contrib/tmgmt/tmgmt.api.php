<?php

/**
 * @file
 * Hooks provided by the Translation Management module.
 */

use Drupal\tmgmt\JobInterface;

/**
 * @addtogroup tmgmt_source
 * @{
 */

/**
 * Alter source plugins information.
 *
 * @param $info
 *   The defined source plugin information.
 */
function hook_tmgmt_source_plugin_info_alter(&$info) {
  $info['test_source']['description'] = t('Updated description');
}

/**
 * Return a list of suggested sources for job items.
 *
 * @param array $items
 *   An array with JobItem objects which must be checked for suggested
 *   translations.
 *   - JobItem A JobItem to check for suggestions.
 *   - ...
 * @param \Drupal\tmgmt\JobInterface $job
 *   The current translation job to check for additional translation items.
 *
 * @return array
 *   An array with all additional translation suggestions.
 *   - job_item: A JobItem instance.
 *   - referenced: A string which indicates where this suggestion comes from.
 *   - from_job: The main Job-ID which suggests this translation.
 */
function hook_tmgmt_source_suggestions(array $items, JobInterface $job) {
  return array(
    array(
      'job_item' => tmgmt_job_item_create('entity', 'node', 0),
      'reason' => t('Referenced @type of field @label', array('@type' => 'entity', '@label' => 'label')),
      'from_item' => $items[1]->id(),
    )
  );
}

/**
 * @} End of "addtogroup tmgmt_source".
 */

/**
 * @addtogroup tmgmt_translator
 * @{
 */

/**
 * Alter information about translator plugins.
 */
function hook_tmgmt_translator_plugin_info_alter(&$info) {
  $info['test_source']['description'] = t('Updated description');
}

/**
 * @} End of "addtogroup tmgmt_translator".
 */

/**
 * @defgroup tmgmt_job Translation Jobs
 *
 * A single task to translate something into a given language using a @link
 * translator translator @endlink.
 *
 * Attached to these jobs are job items, which specify which @link source
 * sources @endlink are to be translated.
 *
 * To create a new translation job, first create a job and then assign items to
 * each. Each item needs to specify the source plugin that should be used
 * and the type and id, which the source plugin then uses to identify it later
 * on.
 *
 * @code
 * $job = tmgmt_job_create('en', $target_language);
 *
 * for ($i = 1; $i < 3; $i++) {
 *   $job->addItem('test_source', 'test', $i);
 * }
 * @endcode
 *
 * Once a job has been created, it can be assigned to a translator plugin, which
 * is the service that is going to do the translation.
 *
 * @code
 * $job->translator = 'test_translator';
 * // Translator specific settings.
 * $job->settings = array(
 *   'priority' => 5,
 * );
 * $job->save();
 *
 * // Get the translator plugin and request a translation.
 * if ($job->isTranslatable()) {
 *   $job->requestTranslation();
 * }
 * @endcode
 *
 * The translation plugin will then request the text from the source plugin.
 * Depending on the plugin, the text might be sent to an external service
 * or assign it to a local user or team of users. At some point, a translation
 * will be returned and saved in the job items.
 *
 * The translation can now be reviewed, accepted and the source plugins be told
 * to save the translation.
 *
 * @code
 * $job->accepted('Optional message');
 * @endcode
 */

/**
 * @defgroup tmgmt_translator Translators
 *
 * A translator plugin integrates a translation service.
 *
 * To define a translator, hook_tmgmt_translator_plugin_info() needs to be
 * implemented and a controller class (specified in the info) created.
 *
 * A translator plugin is then responsible for sending out a translation job and
 * storing the translated texts back into the job and marking it as needs review
 * once it's finished.
 *
 * TBD.
 */

/**
 * @defgroup tmgmt_source Translation source
 *
 * A source plugin represents translatable elements on a site.
 *
 * For example nodes, but also plain strings, menu items, other entities and so
 * on.
 *
 * To define a source, hook_tmgmt_source_plugin_info() needs to be implemented
 * and a controller class (specified in the info) created.
 *
 * A source has three separate tasks.
 *
 * - Allows to create a new @link job translation job @endlink and assign job
 *   items to itself.
 * - Extract the translatable text into a nested array when
 *   requested to do in their implementation of
 *   SourcePluginControllerInterface::getData().
 * - Save the accepted translations returned by the translation plugin in their
 *   sources in their implementation of
 *   SourcePluginControllerInterface::saveTranslation().
 */

/**
 * @defgroup tmgmt_remote_languages_mapping Remote languages mapping
 *
 * Logic to deal with different language codes at client and server that stand
 * for the same language.
 *
 * For those plugins where such feature has no use there is a plugin setting
 * "map_remote_languages" which can be set to FALSE.
 *
 * @section mappings_info Mappings info
 *
 * There are several methods defined by
 * TranslatorInterface and implemented in
 * Translator that deal with mappings info.
 *
 * - getRemoteLanguagesMappings() - provides pairs of local_code => remote_code.
 * - mapToRemoteLanguage() - helpers to map local/remote.
 *
 * The above methods should not need reimplementation unless special logic is
 * needed. However following methods provide only the fallback behaviour and
 * therefore it is recommended that each plugin provides its specific
 * implementation.
 *
 * - getDefaultRemoteLanguagesMappings() - we might know some mapping pairs
 *   prior to configuring a plugin, this is the place where we can define these
 *   mappings. The default implementation returns an empty array.
 * - getSupportedRemoteLanguages() - gets array of language codes in
 * lang_code => lang_code format. It says with what languages the remote
 * system can deal with. These codes are in the remote format.
 *
 * @section mapping_remote_to_local Mapping remote to local
 *
 * Mapping remote to local language codes is done when determining the
 * language capabilities of the remote system. All following logic should then
 * solely work with local language codes. There are two methods defined by
 * the TranslatorInterface interface. To do the mapping
 * a plugin must implement getSupportedTargetLanguages() defined in
 * the TranslatorPluginInterface.
 *
 * - getSupportedTargetLanguages() - should return the remote language codes. So
 *   this method provides the remote language codes to the translator.
 * - getSupportedLanguagePairs() - gets language pairs for which translations
 *   can be done. The language codes must be in remote form. The default
 *   implementation uses getSupportedTargetLanguages() so mapping occur.
 *
 * @section mapping_local_to_remote Mapping local to remote
 *
 * Mapping of local to remote language codes is done upon translation job
 * request in the TranslatorPluginControllerInterface::requestTranslation()
 * plugin implementation.
 */

/**
 * @defgroup tmgmt_cart Translation cart
 *
 * The translation cart can collect multiple source items of different types
 * which are meant for translation into a list. The list then provides
 * functionality to request translation of the items into multiple target
 * languages.
 *
 * Each source can easily plug into the cart system utilising the
 * tmgmt_add_cart_form() on either the source overview page as well as the
 * translate tab.
 */

/**
 * Allows to alter job checkout workflow before the default behavior kicks in.
 *
 * Note: The default behavior will ignore jobs that have already been checked
 * out. Remove jobs from the array to prevent the default behavior for them.
 *
 * @param \Drupal\tmgmt\JobInterface[] $remaining_jobs
 *   List of redirects the user is supposed to be redirected to.
 * @param \Drupal\tmgmt\JobInterface[] $jobs
 *   Array with the translation jobs to be checked out.
 */
function hook_tmgmt_job_checkout_before_alter(&$remaining_jobs, &$jobs) {
  foreach ($jobs as $job) {
    // Automatically check out all jobs using the default settings.
    $job->translator = 'example';
    $job->translator_context = $job->getTranslator()->getPlugin()->defaultCheckoutSettings();
  }
}

/**
 * Allows to alter job checkout workflow after the default behavior.
 *
 * @param $redirects
 *   List of redirects the user is supposed to be redirected to.
 * @param $jobs
 *   Array with the translation jobs to be checked out.
 */
function hook_tmgmt_job_checkout_after_alter(&$redirects, &$jobs) {
  // Redirect to custom multi-checkout form if there are multple redirects.
  if (count($redirects) > 2) {
    $redirects = array('/my/custom/checkout/form/' . implode(',', array_keys($jobs)));
  }
}

/**
 * Allows to alter job checkout workflow before the default behavior.
 *
 * @param \Drupal\tmgmt\JobInterface $job
 *   The Job being submitted.
 */
function hook_tmgmt_job_before_request_translation(JobInterface $job) {
  /** @var \Drupal\tmgmt\Data $data_service */
  $data_service = \Drupal::service('tmgmt.data');

  // Do changes to the data for example.
  foreach ($job->getItems() as $job_item) {
    $unfiltered_data = $job_item->getData();
    $data_items = $data_service->filterTranslatable($unfiltered_data);
    foreach ($data_items as $data_item) {
      $data_item['property'] = 'new value';
    }
  }
}

/**
 * Allows to alter job checkout workflow after the default behavior.
 *
 * @param \Drupal\tmgmt\JobInterface $job
 *   The Job being submitted.
 */
function hook_tmgmt_job_after_request_translation(JobInterface $job) {
  /** @var \Drupal\tmgmt\Data $data_service */
  $data_service = \Drupal::service('tmgmt.data');

  // Reset the previous done changes to the data for example.
  foreach ($job->getItems() as $job_item) {
    $unfiltered_data = $job_item->getData();
    $data_items = $data_service->filterTranslatable($unfiltered_data);
    foreach ($data_items as $data_item) {
      $data_item['property'] = 'old value';
    }
  }
}

/**
 * Allows to alter a text's segment masking the HTML tags from a tmgmt-tag.
 *
 * @param string $source_text
 *   The source's text segment to alter.
 * @param string $translation_text
 *   The translation's text segment to alter.
 * @param array $context
 *   An associative array containing:
 *   - data_item: The data item.
 *   - job_item: The job item context.
 */
function hook_tmgmt_data_item_text_output_alter(&$source_text, &$translation_text, array $context) {
  $source_text = str_replace('First', 'Second', $source_text);
  $translation_text = str_replace('First', 'Second', $translation_text);
}

/**
 * Allows to alter a text's segment unmasking the HTML tags into a tmgmt-tag.
 *
 * @param string $translation_text
 *   The translation's text segment to alter.
 * @param array $context
 *   An associative array containing:
 *   - data_item: The data item.
 *   - job_item: The job item context.
 */
function hook_tmgmt_data_item_text_input_alter(&$translation_text, array $context) {
  $translation_text = str_replace('Second', 'First', $translation_text);
}

/**
 * Allows to alter job state definitions.
 *
 * @param array $definitions
 *   The definitions array.
 *
 * @see \Drupal\tmgmt\Entity\JobItem::getStateDefinitions()
 */
function hook_tmgmt_job_item_state_definitions_alter(&$definitions) {

}
