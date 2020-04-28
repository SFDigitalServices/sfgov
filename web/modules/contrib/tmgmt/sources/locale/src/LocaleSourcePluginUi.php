<?php

namespace Drupal\tmgmt_locale;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\SourcePluginUiBase;

/**
 * Locale source plugin UI.
 *
 * Plugin UI for i18n strings translation jobs.
 */
class LocaleSourcePluginUi extends SourcePluginUiBase {

  /**
   * Gets locale strings.
   *
   * @param string $textgroup
   *   The locale textgroup.
   * @param string $search_label
   *   Label to search for.
   * @param string $missing_target_language
   *   Missing translation language.
   * @param string $context
   *   The translation context.
   *
   * @return array
   *   List of i18n strings data.
   */
  function getStrings($search_label = NULL, $missing_target_language = NULL, $context = NULL) {
    $langcodes = array_keys( \Drupal::languageManager()->getLanguages());
    $languages = array_combine($langcodes, $langcodes);
    $select = \Drupal::database()->select('locales_source', 'ls')
      ->fields('ls', array('lid', 'source', 'context'));

    if (!empty($search_label)) {
      $select->condition('ls.source', '%' . \Drupal::database()->escapeLike($search_label) . '%', 'LIKE');
    }
    if (!empty($context)) {
      $select->condition('ls.context', $context);
    }
    if (!empty($missing_target_language) && in_array($missing_target_language, $languages)) {
      $select->isNull("lt_$missing_target_language.language");
    }

    // Join locale targets for each language.
    // We want all joined fields to be named as langcodes, but langcodes could
    // contain hyphens in their names, which is not allowed by the most database
    // engines. So we create a langcode-to-filed_alias map, and rename fields
    // later.
    $langcode_to_filed_alias_map = array();
    foreach ($languages as $langcode) {
      $table_alias = $select->leftJoin('locales_target', \Drupal::database()->escapeTable("lt_$langcode"), "ls.lid = %alias.lid AND %alias.language = '$langcode'");
      $langcode_to_filed_alias_map[$langcode] = $select->addField($table_alias, 'language');
    }
    unset($field_alias);

    $rows = $select
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit( \Drupal::config('tmgmt.settings')->get('source_list_limit', 20))
      ->execute()
      ->fetchAll();
    foreach ($rows as $row) {
      foreach ($langcode_to_filed_alias_map as $langcode => $field_alias) {
        $row->{$langcode} = $row->{$field_alias};
        unset($row->{$field_alias});
      }
    }
    unset($row);

    return $rows;
  }

  /**
   * Gets overview form header.
   *
   * @return array
   *   Header array definition as expected by theme_tablesort().
   */
  public function overviewFormHeader() {
    $languages = array();
    foreach ($this->getLanguages() as $langcode => $language) {
      $languages['langcode-' . $langcode] = array(
        'data' => $language->getName(),
      );
    }

    $header = array(
        'source' => array('data' => t('Source text')),
        'context' => array('data' => t('Context')),
      ) + $languages;

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function overviewForm(array $form, FormStateInterface $form_state, $type) {
    $form = parent::overviewForm($form, $form_state, $type);
    $search_data = $this->getSearchFormSubmittedParams();

    $form['items']['#empty'] = $this->t('No strings matching given criteria have been found.');

    $strings = $this->getStrings($search_data['label'], $search_data['missing_target_language'], $search_data['context']);

    foreach ($this->getTranslationData($strings, $type) as $id => $data) {
      $form['items']['#options'][$id] = $this->overviewRow($type, $data);
    }

    $form['pager'] = array('#type' => 'pager');

    return $form;
  }

  /**
   * Helper function to create translation data list for the sources page list.
   *
   * @param array $strings
   *   Result of the search query returned by tmgmt_i18n_string_get_strings().
   * @param string $type
   *   I18n object type.
   *
   * @return array
   *   Structured array with translation data.
   */
  protected function getTranslationData($strings, $type) {
    $objects = array();
    // Source language of locale strings is always english.
    $source_language = 'en';

    foreach ($strings as $string) {
      $id = $string->lid;

      // Get existing translations and current job items for the entity
      // to determine translation statuses
      $current_job_items = tmgmt_job_item_load_latest('locale', $type, $id, $source_language);

      $objects[$id] = array(
        'id' => $id,
        'object' => $string,
      );

      // Load entity translation specific data.
      foreach ($this->getLanguages() as $langcode => $language) {

        $translation_status = 'current';

        if ($langcode == $source_language) {
          $translation_status = 'original';
        }
        elseif ($string->{$langcode} === NULL) {
          $translation_status = 'missing';
        }

        $objects[$id]['current_job_items'][$langcode] = isset($current_job_items[$langcode]) ? $current_job_items[$langcode] : NULL;
        $objects[$id]['translation_statuses'][$langcode] = $translation_status;
      }
    }

    return $objects;
  }

  /**
   * {@inheritdoc}
   */
  public function overviewSearchFormPart(array $form, FormStateInterface $form_state, $type) {
    $form = parent::overviewSearchFormPart($form, $form_state, $type);

    $options = array();
    foreach (\Drupal::languageManager()->getLanguages() as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }

    $default_values = $this->getSearchFormSubmittedParams();
    $contexts = $this->getContexts();

    $form['search_wrapper']['search']['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Source text'),
      '#default_value' => isset($default_values['label']) ? $default_values['label'] : NULL,
    );

    // Unset English as it is the source language for all locale strings.
    unset($options['en']);

    $form['search_wrapper']['search']['missing_target_language'] = array(
      '#type' => 'select',
      '#title' => t('Not translated to'),
      '#options' => $options,
      '#empty_option' => '--',
      '#default_value' => isset($default_values['missing_target_language']) ? $default_values['missing_target_language'] : NULL,
    );

    $form['search_wrapper']['search']['context'] = [
      '#type' => 'select',
      '#title' => t('Context'),
      '#empty_option' => '--',
      '#options' => $contexts,
      '#default_value' => isset($default_values['context']) ? $default_values['context'] : NULL,
    ];

    return $form;
  }

  /**
   * Gets submitted search params.
   *
   * @return array
   */
  public function getSearchFormSubmittedParams() {
    $params = array(
      'label' => NULL,
      'missing_target_language' => NULL,
      'context' => NULL,
    );

    if (isset($_GET['label'])) {
      $params['label'] = $_GET['label'];
    }
    if (isset($_GET['missing_target_language'])) {
      $params['missing_target_language'] = $_GET['missing_target_language'];
    }
    if (isset($_GET['context'])) {
      $params['context'] = $_GET['context'];
    }

    return $params;
  }

  /**
   * Gets a list of contexts in the system.
   *
   * @return array
   */
  public function getContexts() {
    $query = \Drupal::database()->select('locales_source', 'ls');
    $query->fields('ls', ['context', 'context']);
    $query->distinct();
    $query->orderBy('ls.context');
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * Builds a table row for overview form.
   *
   * @param string $type
   *   i18n type.
   * @param array $data
   *   Data needed to build the list row.
   *
   * @return array
   */
  public function overviewRow($type, $data) {
    // Set the default item key, assume it's the first.
    $source = $data['object'];

    $row = array(
      'id' => $data['id'],
      'source' => $source->source,
      'context' => $source->context,
    );

    foreach (\Drupal::languageManager()->getLanguages() as $langcode => $language) {
      $build = $this->buildTranslationStatus($data['translation_statuses'][$langcode], isset($data['current_job_items'][$langcode]) ? $data['current_job_items'][$langcode] : NULL);
      $row['langcode-' . $langcode] = \Drupal::service('renderer')->render($build);
    }

    return $row;
  }

  /**
   * Returns languages, ensures that english is first.
   *
   * @return array
   */
  protected function getLanguages() {
    // Make sure that en is the first language.
    $languages = \Drupal::languageManager()->getLanguages();
    $en = $languages['en'];
    unset($languages['en']);
    $languages = array('en' => $en) + $languages;
    return $languages;
  }
}
