<?php

namespace Drupal\tmgmt;

use Drupal\Core\Plugin\PluginBase;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\Translator\TranslatableResult;

/**
 * Default controller class for service plugins.
 *
 * @ingroup tmgmt_translator
 */
abstract class TranslatorPluginBase extends PluginBase implements TranslatorPluginInterface {

  /**
   * Characters that indicate the beginning of an escaped string.
   *
   * @var string
   */
  protected $escapeStart = '';

  /**
   * Characters that indicate the end of an escaped string.
   *
   * @var string
   */
  protected $escapeEnd = '';

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    // Assume that the translation service is always available.
    return AvailableResult::yes();
  }

  /**
   * {@inheritdoc}
   */
  public function checkTranslatable(TranslatorInterface $translator, JobInterface $job) {
    // The job is only translatable if the translator is available too.
    if ($this->checkAvailable($translator)->getSuccess() && array_key_exists($job->getTargetLangcode(), $translator->getSupportedTargetLanguages($job->getSourceLangcode()))) {
      // We can only translate this job if the target language of the job is in
      // one of the supported languages.
      return TranslatableResult::yes();
    }
    return TranslatableResult::no(t('@translator can not translate from @source to @target.', array('@translator' => $translator->label(), '@source' => $job->getSourceLanguage()->getName(), '@target' => $job->getTargetLanguage()->getName())));
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation(JobInterface $job) {
    // Assume that we can abort a translation job at any time.
    $job->aborted();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRemoteLanguagesMappings() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $languages = tmgmt_available_languages();
    return array_combine(array_keys($languages), array_keys($languages));
  }

  /**
   * {@inheritdoc}
   *
   * Default implementation that gets target languages for each remote language.
   * This approach is ineffective and therefore it is advised that a plugin
   * should provide own implementation.
   */
  public function getSupportedLanguagePairs(TranslatorInterface $translator) {
    $language_pairs = array();

    foreach ($this->getSupportedRemoteLanguages($translator) as $source_language) {
      foreach ($this->getSupportedTargetLanguages($translator, $source_language) as $target_language) {
        $language_pairs[] = array('source_language' => $source_language, 'target_language' => $target_language);
      }
    }

    return $language_pairs;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    $defaults = array();
    // Check if any default settings are defined in the plugin info.
    if (isset($this->pluginDefinition['default_settings'])) {
      return array_merge($defaults, $this->pluginDefinition['default_settings']);
    }
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCheckoutSettings(JobInterface $job) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptedDataItem(JobItemInterface $job_item, array $key) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function escapeText(array $data_item) {
    if (empty($data_item['#escape'])) {
      return $data_item['#text'];
    }

    $text = $data_item['#text'];
    $escape = $data_item['#escape'];

    // Sort them in reverse order based/ on the position and process them,
    // so that positions don't change.
    krsort($escape, SORT_NUMERIC);

    foreach ($escape as $position => $info) {
      $text = substr_replace($text, $this->getEscapedString($info['string']), $position, strlen($info['string']));
    }

    return $text;
  }

  /**
   * Returns the escaped string.
   *
   * Defaults to use the escapeStart and escapeEnd properties but can be
   * overriden if a non-static replacement pattern is used.
   *
   * @param string $string
   *   String that should be escaped.
   * @return string
   */
  protected function getEscapedString($string) {
    return $this->escapeStart . $string . $this->escapeEnd;
  }

  /**
   * {@inheritdoc}
   */
  public function unescapeText($text) {
    return preg_replace('/' . preg_quote($this->escapeStart, '/') . '(.+)' . preg_quote($this->escapeEnd, '/') . '/U', '$1', $text);
  }

}
