<?php

namespace Drupal\tmgmt;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for the tmgmt_translator entity.
 *
 * @ingroup tmgmt_translator
 */
interface TranslatorInterface extends ConfigEntityInterface {

  /**
   * Returns the array of settings.
   *
   * See the documentation of the translator plugin for supported or
   * required settings.
   *
   * @return array
   *   The array of settings.
   */
  public function getSettings();

  /**
   * Sets the array of settings.
   *
   * @param array $settings
   *   The array of settings.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function setSettings(array $settings);

  /**
   * Retrieves a setting value from the translator settings.
   *
   * Pulls the default values (if defined) from the plugin controller.
   *
   * @param string|array $name
   *   The name of the setting, an array with multiple keys for nested settings.
   *
   * @return string
   *   The setting value or $default if the setting value is not set. Returns
   *   NULL if the setting does not exist at all.
   */
  public function getSetting($name);

  /**
   * Sets a definition setting.
   *
   * @param string|array $setting_name
   *   The definition setting to set.
   * @param mixed $value
   *   The value to set.
   *
   * @return static
   *   The object itself for chaining.
   */
  public function setSetting($setting_name, $value);

  /**
   * Checks if it can skip the reviewing process and automatically accepts all translations.
   *
   * @return bool
   *   TRUE if it can skip the reviewing process, FALSE otherwise.
   */
  public function isAutoAccept();

  /**
   * Sets whether to skip the reviewing process and automatically accept all translations.
   *
   * @param bool
   *   The value to set.
   *
   * @return $this
   */
  public function setAutoAccept($value);

  /**
   * Returns the translator plugin ID.
   *
   * @return string
   *   The translator plugin ID used by this translator.
   */
  public function getPluginId();

  /**
   * Returns the translator plugin ID.
   *
   * @return string
   *   The translator plugin ID used by this translator.
   */
  public function getDescription();

  /**
   * Sets the plugin ID.
   *
   * @param string $plugin_id
   *   The plugin ID.
   */
  public function setPluginID($plugin_id);

  /**
   * Returns the translator plugin of this translator.
   *
   * @return \Drupal\tmgmt\TranslatorPluginInterface
   *   Returns the TranslatorPluginInterface.
   */
  public function getPlugin();

  /**
   * Checks if the translator plugin of this translator exists.
   *
   * @return bool
   *   Returns TRUE if it exists, FALSE otherwise.
   */
  public function hasPlugin();

  /**
   * Returns the supported target languages for this translator.
   *
   * @param string $source_language
   *   The local source language.
   *
   * @return array
   *   An array of supported target languages in ISO format.
   */
  public function getSupportedTargetLanguages($source_language);

  /**
   * Gets the supported language pairs for this translator.
   *
   * @return array
   *   List of language pairs where a pair is an associative array of
   *   source_language and target_language.
   *   Example:
   *   array(
   *     array('source_language' => 'en-US', 'target_language' => 'de-DE'),
   *     array('source_language' => 'en-US', 'target_language' => 'de-CH'),
   *   )
   */
  public function getSupportedLanguagePairs();

  /**
   * Gets all supported languages of the translator plugin.
   *
   * @return array
   *   An array of language codes which are provided by the translator plugin
   *   (remote language codes).
   */
  public function getSupportedRemoteLanguages();

  /**
   * Clears the language cache for this translator.
   */
  public function clearLanguageCache();

  /**
   * Check whether this translator can handle a particular translation job.
   *
   * @param \Drupal\tmgmt\JobInterface Job
   *   The Job entity that should be translated.
   *
   * @return \Drupal\tmgmt\Translator\TranslatableResult
   *   TRUE if the job can be processed and translated, FALSE otherwise.
   */
  public function checkTranslatable(JobInterface $job);

  /**
   * Checks whether a translator is available.
   *
   * @return \Drupal\tmgmt\Translator\AvailableResult
   *   TRUE if the translator plugin is available, FALSE otherwise.
   */
  public function checkAvailable();

  /**
   * Returns if the plugin has any settings for this job.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The Job entity that should be translated.
   */
  public function hasCheckoutSettings(JobInterface $job);

  /**
   * Gets existing remote languages mappings.
   *
   * This method is responsible to provide all local to remote language pairs.
   *
   * @return array
   *   An array of local => remote language codes
   *
   * @ingroup tmgmt_remote_languages_mapping
   */
  public function getRemoteLanguagesMappings();

  /**
   * Maps remote languages to local languages.
   *
   * Returns a list of local languages that can be mapped to any of the
   * remote languages.
   *
   * @param string[] $remote_languages
   *   Remote language codes.
   *
   * @return string[]
   *   Local language codes.
   *
   * @ingroup tmgmt_remote_languages_mapping
   */
  public function mapToLocalLanguages(array $remote_languages);

  /**
   * Maps local language to remote language.
   *
   * @param string $language
   *   Local language code.
   *
   * @return string
   *   Remote language code.
   *
   * @ingroup tmgmt_remote_languages_mapping
   */
  public function mapToRemoteLanguage($language);

  /**
   * Determines if this translator supports remote language mappings.
   *
   * @return bool
   *   In case translator does not explicitly state that it does not provide the
   *   mapping feature it will return TRUE.
   */
  public function providesRemoteLanguageMappings();

  /**
   * Determines if job settings of the translator will be handled by its plugin.
   *
   * @return bool
   *   If job settings are to be handled by the plugin.
   */
  public function hasCustomSettingsHandling();

}
