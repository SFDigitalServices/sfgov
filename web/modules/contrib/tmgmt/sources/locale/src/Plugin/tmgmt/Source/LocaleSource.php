<?php

namespace Drupal\tmgmt_locale\Plugin\tmgmt\Source;

use Drupal\Component\Utility\Unicode;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\SourcePluginBase;
use Drupal\tmgmt\TMGMTException;

/**
 * Translation Source plugin for locale strings.
 *
 * @SourcePlugin(
 *   id = "locale",
 *   label = @Translation("Locale"),
 *   description = @Translation("Source handler for locale strings."),
 *   ui = "Drupal\tmgmt_locale\LocaleSourcePluginUi"
 * )
 */
class LocaleSource extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getItemTypes() {
    return array('default' => t('Locale'));
  }

  /**
   * Updates translation associated to a specific locale source.
   *
   * @param string $lid
   *   The Locale ID.
   * @param string $target_language
   *   Target language to update translation.
   * @param string $translation
   *   Translation value.
   *
   * @return bool
   *   Success or not updating the locale translation.
   */
  protected function updateTranslation($lid, $target_language, $translation) {

    $languages = locale_translatable_language_list('name', TRUE);
    if (!$lid || !array_key_exists($target_language, $languages) || !$translation) {
      return FALSE;
    }

    $exists = \Drupal::database()->query("SELECT COUNT(lid) FROM {locales_target} WHERE lid = :lid AND language = :language", array(
      ':lid' => $lid,
      ':language' => $target_language
    ))
      ->fetchField();

    // @todo Only singular strings are managed here, we should take care of
    //   plural information of processed string.
    if (!$exists) {
      $fields = array(
        'lid' => $lid,
        'language' => $target_language,
        'translation' => $translation,
        'customized' => LOCALE_CUSTOMIZED,
      );
      \Drupal::database()->insert('locales_target')
        ->fields($fields)
        ->execute();
    }
    else {
      $fields = array(
        'translation' => $translation,
        'customized' => LOCALE_CUSTOMIZED,
      );
      \Drupal::database()->update('locales_target')
        ->fields($fields)
        ->condition('lid', $lid)
        ->condition('language', $target_language)
        ->execute();
    }
    // Clear locale caches.
    _locale_invalidate_js($target_language);

    \Drupal::cache()->delete('locale:' . $target_language);
    return TRUE;
  }

  /**
   * Helper function to obtain a locale object for given job item.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *
   * @return locale object
   */
  protected function getLocaleObject(JobItemInterface $job_item) {
    $locale_lid = $job_item->getItemId();

    // Check existence of assigned lid.
    $exists = \Drupal::database()->query("SELECT COUNT(lid) FROM {locales_source} WHERE lid = :lid", array(':lid' => $locale_lid))->fetchField();
    if (!$exists) {
      throw new TMGMTException(t('Unable to load locale with id %id', array('%id' => $job_item->getItemId())));
    }

    // This is necessary as the method is also used in the getLabel() callback
    // and for that case the job is not available in the cart.
    if ($job_item->getJobId()) {
      $source_language = $job_item->getJob()->getSourceLangcode();
    }
    else {
      $source_language = $job_item->getSourceLangCode();
    }

    if ($source_language == 'en') {
      $query = \Drupal::database()->select('locales_source', 'ls');
      $query
        ->fields('ls')
        ->condition('ls.lid', $locale_lid);
      $locale_object = $query
        ->execute()
        ->fetchObject();

      $locale_object->language = 'en';

      if (empty($locale_object)) {
        return NULL;
      }

      $locale_object->origin = 'source';
    }
    else {
      $query = \Drupal::database()->select('locales_target', 'lt');
      $query->join('locales_source', 'ls', 'ls.lid = lt.lid');
      $query
        ->fields('lt')
        ->fields('ls')
        ->condition('lt.lid', $locale_lid)
        ->condition('lt.language', $source_language);
      $locale_object = $query
        ->execute()
        ->fetchObject();

      if (empty($locale_object)) {
        return NULL;
      }

      $locale_object->origin = 'target';
    }

    return $locale_object;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(JobItemInterface $job_item) {
    if ($locale_object = $this->getLocaleObject($job_item)) {
      if ($locale_object->origin == 'source') {
        $label = $locale_object->source;
      }
      else {
        $label = $locale_object->translation;
      }
      return Unicode::truncate(strip_tags($label), 30, FALSE, TRUE);
    }
  }

  /**
   * [@inheritdoc}
   */
  public function getType(JobItemInterface $job_item) {
    return $this->getItemTypeLabel($job_item->getItemType());
  }

  /**
   * {@inheritdoc}
   */
  public function getData(JobItemInterface $job_item) {
    $locale_object = $this->getLocaleObject($job_item);
    if (empty($locale_object)) {
      throw new TMGMTException(t('Unable to load %language translation for the locale %id',
        array('%language' => $job_item->getJob()->getSourceLanguage()->getName(), '%id' => $job_item->getItemId())));
    }
    if ($locale_object->origin == 'source') {
      $text = $locale_object->source;
    }
    else {
      $text = $locale_object->translation;
    }

    // Identify placeholders that need to be escaped. Assume that placeholders
    // consist of alphanumeric characters and _,- only and are delimited by
    // non-alphanumeric characters. There are cases that don't match, for
    // example appended SI units like "@valuems", there only @value is the
    // actual placeholder.
    $escape = array();
    if (preg_match_all('/([@!%][a-zA-Z0-9_-]+)/', $text, $matches, PREG_OFFSET_CAPTURE)) {
      foreach ($matches[0] as $match) {
        $escape[$match[1]]['string'] = $match[0];
      }
    }
    $structure['singular'] = array(
      '#label' => t('Singular'),
      '#text' => (string) $text,
      '#translate' => TRUE,
      '#escape' => $escape,
    );
    return $structure;
  }

  /**
   * {@inheritdoc}
   */
  public function saveTranslation(JobItemInterface $job_item, $target_langcode) {
    $data = $job_item->getData();
    if (isset($data['singular'])) {
      $translation = $data['singular']['#translation']['#text'];
      // Update the locale string in the system.
      // @todo: Send error message to user if update fails.
      if ($this->updateTranslation($job_item->getItemId(), $target_langcode, $translation)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLangCode(JobItemInterface $job_item) {
    // For the locale source English is always the source language.
    return 'en';
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingLangCodes(JobItemInterface $job_item) {
    $query = \Drupal::database()->select('locales_target', 'lt');
    $query->fields('lt', array('language'));
    $query->condition('lt.lid', $job_item->getItemId());

    $existing_lang_codes = array('en');
    foreach ($query->execute() as $language) {
      $existing_lang_codes[] = $language->language;
    }

    return $existing_lang_codes;
  }

}
