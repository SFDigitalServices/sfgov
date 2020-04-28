<?php

namespace Drupal\tmgmt\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\Translator\TranslatableResult;
use Drupal\tmgmt\TranslatorInterface;

/**
 * Entity class for the tmgmt_translator entity.
 *
 * For disambiguation, The UI uses the term "Provider" for a translator.
 *
 * @ConfigEntityType(
 *   id = "tmgmt_translator",
 *   label = @Translation("Provider"),
 *   handlers = {
 *     "form" = {
 *       "edit" = "Drupal\tmgmt\Form\TranslatorForm",
 *       "add" = "Drupal\tmgmt\Form\TranslatorForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *       "clone" = "Drupal\tmgmt\Form\TranslatorForm",
 *     },
 *     "list_builder" = "Drupal\tmgmt\Entity\ListBuilder\TranslatorListBuilder",
 *     "access" = "Drupal\tmgmt\Entity\Controller\TranslatorAccessControlHandler",
 *   },
 *   uri_callback = "tmgmt_translator_uri",
 *   config_prefix = "translator",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "description",
 *     "auto_accept",
 *     "weight",
 *     "plugin",
 *     "settings",
 *     "remote_languages_mappings",
 *   },
 *   links = {
 *     "collection" = "/admin/tmgmt/translators",
 *     "edit-form" = "/admin/tmgmt/translator/manage/{tmgmt_translator}",
 *     "add-form" = "/admin/tmgmt/translators/add",
 *     "delete-form" = "/admin/tmgmt/translators/manage/{tmgmt_translator}/delete",
 *     "clone-form" = "/admin/tmgmt/translators/manage/{tmgmt_translator}/clone",
 *   }
 * )
 *
 * @ingroup tmgmt_translator
 */
class Translator extends ConfigEntityBase implements TranslatorInterface {

  /**
   * Machine readable name of the translator.
   *
   * @var string
   */
  protected $name;

  /**
   * The UUID of this translator.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Label of the translator.
   *
   * @var string
   */
  protected $label;

  /**
   * Description of the translator.
   *
   * @var string
   */
  protected $description;

  /**
   * Weight of the translator.
   *
   * @var int
   */
  protected $weight;

  /**
   * Plugin name of the translator.
   *
   * @type string
   */
  protected $plugin;

  /**
   * Translator type specific settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * Whether to skip reviewing process and auto accepting translation.
   *
   * @var bool
   */
  protected $auto_accept;

  /**
   * The supported target languages caches.
   *
   * @var array
   */
  protected $languageCache;

  /**
   * The supported language pairs caches.
   *
   * @var array
   */
  protected $languagePairsCache;

  /**
   * The supported remote languages caches.
   *
   * @var array
   */
  protected $remoteLanguages = [];

  /**
   * Whether the language cache in the database is outdated.
   *
   * @var bool
   */
  protected $languageCacheOutdated;

  /**
   * The remote languages mappings.
   *
   * @var array
   */
  protected $remote_languages_mappings = array();

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name) {
    if (is_array($name)) {
      if (NestedArray::keyExists($this->settings, $name)) {
        return NestedArray::getValue($this->settings, $name);
      }
      elseif ($plugin = $this->getPlugin()) {
        $defaults = $plugin->defaultSettings();
        return NestedArray::getValue($defaults, $name);
      }
    }
    else {
      if (isset($this->settings[$name])) {
        return $this->settings[$name];
      }
      elseif ($plugin = $this->getPlugin()) {
        $defaults = $plugin->defaultSettings();
        if (isset($defaults[$name])) {
          return $defaults[$name];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($setting_name, $value) {
    NestedArray::setValue($this->settings, (array) $setting_name, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isAutoAccept() {
    return $this->auto_accept;
  }

  /**
   * {@inheritdoc}
   */
  public function setAutoAccept($value) {
    $this->auto_accept = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginID($plugin_id) {
    $this->plugin = $plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    // We are never going to have many entities here, so we can risk a loop.
    foreach ($entities as $key => $name) {
      // Find active jobs associated with the translator that is being deleted.
      $job_ids = \Drupal::entityQuery('tmgmt_job')
        ->condition('state', [Job::STATE_ACTIVE, Job::STATE_CONTINUOUS, Job::STATE_UNPROCESSED], 'IN')
        ->condition('translator', $key)
        ->execute();
      $jobs = Job::loadMultiple($job_ids);
      /** @var \Drupal\tmgmt\JobInterface $job */
      foreach ($jobs as $job) {
        $job->aborted('Job has been aborted because the translation provider %provider was deleted.', ['%provider' => $job->getTranslatorLabel()]);
      }
    }
    parent::preDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return \Drupal::service('plugin.manager.tmgmt.translator')->createInstance($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function hasPlugin() {
    if (!empty($this->plugin) && \Drupal::service('plugin.manager.tmgmt.translator')->hasDefinition($this->plugin)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages($source_language) {
    if ($plugin = $this->getPlugin()) {
      $info = $plugin->getPluginDefinition();
      if (isset($info['language_cache']) && empty($info['language_cache'])) {
        // This plugin doesn't support language caching.
        return $this->mapToLocalLanguages($plugin->getSupportedTargetLanguages($this, $this->mapToRemoteLanguage($source_language)));
      }
      else {
        // Retrieve the supported languages from the cache.
        if (empty($this->languageCache) && $cache = \Drupal::cache('data')->get('tmgmt_languages:' . $this->name)) {
          $this->languageCache = $cache->data;
        }
        // Even if we successfully queried the cache it might not have an entry
        // for our source language yet.
        if (!isset($this->languageCache[$source_language])) {
          $local_languages = $this->mapToLocalLanguages($plugin->getSupportedTargetLanguages($this, $this->mapToRemoteLanguage($source_language)));
          if (empty($local_languages)) {
            return [];
          }
          $this->languageCache[$source_language] = $local_languages;
          $this->updateCache();
        }
      }
      return $this->languageCache[$source_language];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedLanguagePairs() {
    if ($plugin = $this->getPlugin()) {
      $info = $plugin->getPluginDefinition();
      if (isset($info['language_cache']) && empty($info['language_cache'])) {
        // This plugin doesn't support language caching.
        return $plugin->getSupportedLanguagePairs($this);
      }
      else {
        // Retrieve the supported languages from the cache.
        if (empty($this->languagePairsCache) && $cache = \Drupal::cache('data')->get('tmgmt_language_pairs:' . $this->name)) {
          $this->languagePairsCache = $cache->data;
        }
        // Even if we successfully queried the cache data might not be yet
        // available.
        if (empty($this->languagePairsCache)) {
          $this->languagePairsCache = $plugin->getSupportedLanguagePairs($this);
          $this->updateCache();
        }
      }
      return $this->languagePairsCache;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages() {
    if ($plugin = $this->getPlugin()) {
      if (empty($this->remoteLanguages) && $cache = \Drupal::cache('data')->get('tmgmt_remote_languages:' . $this->name)) {
        $this->remoteLanguages = $cache->data;
      }
      if (empty($this->remoteLanguages)) {
        $this->remoteLanguages = $plugin->getSupportedRemoteLanguages($this);
        $info = $plugin->getPluginDefinition();
        if (!isset($info['language_cache']) || !empty($info['language_cache'])) {
          \Drupal::cache('data')->set('tmgmt_remote_languages:' . $this->name, $this->remoteLanguages, Cache::PERMANENT, $this->getCacheTags());
        }
      }
    }
    return $this->remoteLanguages;
  }

  /**
   * {@inheritdoc}
   */
  public function clearLanguageCache() {
    $this->languageCache = array();
    \Drupal::cache('data')->delete('tmgmt_languages:' . $this->name);
    \Drupal::cache('data')->delete('tmgmt_language_pairs:' . $this->name);
    \Drupal::cache('data')->delete('tmgmt_remote_languages:' . $this->name);
  }

  /**
   * {@inheritdoc}
   */
  public function checkTranslatable(JobInterface $job) {
    if ($plugin = $this->getPlugin()) {
      return $plugin->checkTranslatable($this, $job);
    }
    return TranslatableResult::no(t('Missing provider plugin'));
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable() {
    if ($plugin = $this->getPlugin()) {
      return $plugin->checkAvailable($this);
    }
    return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $this->label(),
      ':configured' => $this->toUrl()->toString()
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function hasCheckoutSettings(JobInterface $job) {
    if ($plugin = $this->getPlugin()) {
      return $plugin->hasCheckoutSettings($job);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteLanguagesMappings() {
    $remote_languages_mappings = [];
    foreach (\Drupal::languageManager()->getLanguages() as $language => $info) {
      $remote_languages_mappings[$language] = $this->mapToRemoteLanguage($language);
    }

    return $remote_languages_mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function mapToLocalLanguages(array $remote_languages) {
    $local_languages = array();
    $remote_mappings = $this->getPlugin()->getDefaultRemoteLanguagesMappings();
    foreach ($remote_languages as $language => $info) {
      if (in_array($language, $remote_mappings)) {
        $local_language = array_search($language, $remote_mappings);
        $local_languages[$local_language] = $local_language;
      }
      else {
        $local_languages[$language] = $this->mapToRemoteLanguage($language);
      }
    }
    foreach (\Drupal::languageManager()->getLanguages() as $language => $info) {
      $remote_language = $this->mapToRemoteLanguage($language);
      if (isset($remote_languages[$remote_language])) {
        $local_languages[$language] = $language;
      }
    }
    return $local_languages;
  }

  /**
   * {@inheritdoc}
   */
  public function mapToRemoteLanguage($language) {
    if (!$this->providesRemoteLanguageMappings()) {
      return $language;
    }

    $mapping = $this->get('remote_languages_mappings');
    $remote_languages = $this->getSupportedRemoteLanguages();
    if (!empty($mapping) && array_key_exists($language, $mapping)) {
      if (empty($remote_languages) || array_key_exists($mapping[$language], $remote_languages)) {
        return $mapping[$language];
      }
    }

    $default_mappings = $this->getPlugin()->getDefaultRemoteLanguagesMappings();

    if (isset($default_mappings[$language])) {
      return $default_mappings[$language];
    }

    if ($matching_language = \Drupal::service('tmgmt.language_matcher')->getMatchingLangcode($language, $remote_languages)) {
      return $matching_language;
    }

    return $language;
  }

  /**
   * Updates the language cache.
   */
  protected function updateCache() {
    if ($plugin = $this->getPlugin()) {
      $info = $plugin->getPluginDefinition();
      if (!isset($info['language_cache']) || !empty($info['language_cache'])) {
        \Drupal::cache('data')->set('tmgmt_languages:' . $this->name, $this->languageCache, Cache::PERMANENT, $this->getCacheTags());
        \Drupal::cache('data')->set('tmgmt_language_pairs:' . $this->name, $this->languagePairsCache, Cache::PERMANENT, $this->getCacheTags());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function providesRemoteLanguageMappings() {
    $definition = \Drupal::service('plugin.manager.tmgmt.translator')->getDefinition($this->getPluginId());
    if (!isset($definition['map_remote_languages'])) {
      return TRUE;
    }
    return $definition['map_remote_languages'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasCustomSettingsHandling() {
    $definition = \Drupal::service('plugin.manager.tmgmt.translator')->getDefinition($this->getPluginId());

    if (isset($definition['job_settings_custom_handling'])) {
      return $definition['job_settings_custom_handling'];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    if ($this->getPlugin()) {
      $this->addDependency('module', $this->getPlugin()->getPluginDefinition()['provider']);
    }
    return $this;
  }

}
