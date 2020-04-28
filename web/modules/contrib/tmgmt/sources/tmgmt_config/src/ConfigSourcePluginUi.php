<?php

namespace Drupal\tmgmt_config;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\SourcePluginUiBase;
use Drupal\tmgmt_config\Plugin\tmgmt\Source\ConfigSource;

/**
 * Config source plugin UI.
 *
 * That provides getEntity() method to retrieve list of entities
 * of specific type. It also allows to implement alter hook to alter
 * the entity query for a specific type.
 *
 * @ingroup tmgmt_source
 */
class ConfigSourcePluginUi extends SourcePluginUiBase {

  /**
   * Entity source list items limit.
   *
   * @var int
   */
  public $pagerLimit = 25;

  /**
   * {@inheritdoc}
   */
  public function overviewSearchFormPart(array $form, FormStateInterface $form_state, $type) {
    $form = parent::overviewSearchFormPart($form, $form_state, $type);

    if ($type == ConfigSource::SIMPLE_CONFIG) {
      $label_key = 'name';
      $label = t('Simple configuration');
    }
    else {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($type);

      $label_key = $entity_type->getKey('label');
      $label = $entity_type->getLabel();
    }

    if (!empty($label_key)) {
      $form['search_wrapper']['search'][$label_key] = array(
        '#type' => 'textfield',
        '#title' => t('@entity_name title', array('@entity_name' => $label)),
        '#size' => 25,
        '#default_value' => isset($_GET[$label_key]) ? $_GET[$label_key] : NULL,
      );
    }

    $form['search_wrapper']['search']['langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Source Language'),
      '#empty_option' => t('- Any -'),
      '#default_value' => isset($_GET['langcode']) ? $_GET['langcode'] : NULL,
    );

    $form['search_wrapper']['search']['target_language'] = array(
      '#type' => 'language_select',
      '#title' => $this->t('Target language'),
      '#empty_option' => $this->t('- Any -'),
      '#default_value' => isset($_GET['target_language']) ? $_GET['target_language'] : NULL,
    );
    $form['search_wrapper']['search']['target_status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Target status'),
      '#options' => array(
        'untranslated' => $this->t('Untranslated'),
        'translated' => $this->t('Translated'),
      ),
      '#default_value' => isset($_GET['target_status']) ? $_GET['target_status'] : NULL,
      '#states' => array(
        'invisible' => array(
          ':input[name="search[target_language]"]' => array('value' => ''),
        ),
      ),
    );

    return $form;
  }

  /**
   * Gets overview form header.
   *
   * @return array
   *   Header array definition as expected by theme_tablesort().
   */
  public function overviewFormHeader($type) {
    $header = array(
      'title' => array('data' => $this->t('Title (in source language)')),
      'config_id' => array('data' => $this->t('Configuration ID')),
    );

    $header += $this->getLanguageHeader();

    return $header;
  }

  /**
   * Builds a table row for overview form.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   Data needed to build the list row.
   *
   * @return array
   *   A single table row for the overview.
   */
  public function overviewRow(ConfigEntityInterface $entity) {
    $label = $entity->label() ?: $this->t('@type: @id', array(
      '@type' => $entity->getEntityTypeId(),
      '@id' => $entity->id(),
    ));

    // Get current job items for the entity to determine translation statuses.
    $source_lang = $entity->language()->getId();
    $current_job_items = tmgmt_job_item_load_latest('config', $entity->getEntityTypeId(), $entity->getConfigDependencyName(), $source_lang);
    $row['id'] = $entity->id();
    $definition = \Drupal::entityTypeManager()->getDefinition($entity->bundle());
    $row['config_id'] = $definition->getConfigPrefix() . '.' . $entity->id();

    if ($entity->hasLinkTemplate('edit-form')) {
      $row['title'] = $entity->toLink($label, 'edit-form');
    }
    else {
      // If the entity doesn't have a link we display a label.
      $row['title'] = $label;
    }

    // Load entity translation specific data.
    foreach (\Drupal::languageManager()->getLanguages() as $langcode => $language) {

      $translation_status = 'current';

      if ($langcode == $source_lang) {
        $translation_status = 'original';
      }
      elseif (!$this->isTranslated($langcode, $entity->getConfigDependencyName())) {
        $translation_status = 'missing';
      }

      // @todo Find a way to support marking configuration translations as outdated.

      $build = $this->buildTranslationStatus($translation_status, isset($current_job_items[$langcode]) ? $current_job_items[$langcode] : NULL);
      $row['langcode-' . $langcode] = [
        'data' => \Drupal::service('renderer')->render($build),
        'class' => array('langstatus-' . $langcode),
      ];

    }
    return $row;
  }

  /**
   * Builds a table row for simple configuration.
   *
   * @param array $definition
   *   A definition.
   *
   * @return array
   *   A single table row for the overview.
   */
  public function overviewRowSimple(array $definition) {
    // Get current job items for the entity to determine translation statuses.
    $config_id = $definition['names'][0];
    $source_lang = \Drupal::config($definition['names'][0])->get('langcode') ?: 'en';
    $current_job_items = tmgmt_job_item_load_latest('config', ConfigSource::SIMPLE_CONFIG, $definition['id'], $source_lang);

    $row = array(
      'id' => $definition['id'],
      'title' => $definition['title'],
    );

    // Load entity translation specific data.
    foreach (\Drupal::languageManager()->getLanguages() as $langcode => $language) {

      $translation_status = 'current';

      if ($langcode == $source_lang) {
        $translation_status = 'original';
      }
      elseif (!$this->isTranslated($langcode, $config_id)) {
        $translation_status = 'missing';
      }

      // @todo Find a way to support marking configuration translations as outdated.

      $build = $this->buildTranslationStatus($translation_status, isset($current_job_items[$langcode]) ? $current_job_items[$langcode] : NULL);
      $row['langcode-' . $langcode] = [
        'data' => \Drupal::service('renderer')->render($build),
        'class' => array('langstatus-' . $langcode),
      ];
    }
    return $row;
  }

  /**
   * Checks, if an entity is translated.
   *
   * @param string $langcode
   *   Language code.
   * @param string $name
   *   Configuration name.
   *
   * @return bool
   *   Returns TRUE, if it is translatable.
   */
  public function isTranslated($langcode, $name) {
    $config = \Drupal::languageManager()->getLanguageConfigOverride($langcode, $name);
    return !$config->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function overviewForm(array $form, FormStateInterface $form_state, $type) {
    $form = parent::overviewForm($form, $form_state, $type);

    // Load search property params which will be passed into
    $search_property_params = array();
    $exclude_params = array('q', 'page');
    foreach (\Drupal::request()->query->all() as $key => $value) {
      // Skip exclude params, and those that have empty values, as these would
      // make it into query condition instead of being ignored.
      if (in_array($key, $exclude_params) || $value === '') {
        continue;
      }
      $search_property_params[$key] = $value;
    }

    // If the source is of type '_simple_config', we get all definitions that
    // don't have an entity type and display them through overviewRowSimple().
    if ($type == ConfigSource::SIMPLE_CONFIG) {
      $definitions = $this->getFilteredSimpleConfigDefinitions($search_property_params);
      foreach ($definitions as $definition_id => $definition) {
        $form['items']['#options'][$definition_id] = $this->overviewRowSimple($definition);
      }
    }
    // If there is an entity type, list all entities for that.
    else {
      foreach ($this->getTranslatableEntities($type, $search_property_params) as $entity) {
        $form['items']['#options'][$entity->getConfigDependencyName()] = $this->overviewRow($entity);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function overviewFormValidate(array $form, FormStateInterface $form_state, $type) {
    $target_language = $form_state->getValue(array('search', 'target_language'));
    if (!empty($target_language) && $form_state->getValue(array('search', 'langcode')) == $target_language) {
      $form_state->setErrorByName('search[target_language]', $this->t('The source and target languages must not be the same.'));
    }
  }

  /**
   * Gets translatable entities of a given type.
   *
   * Additionally you can specify entity property conditions, pager and limit.
   *
   * @param string $entity_type_id
   *   Drupal entity type.
   * @param array $property_conditions
   *   Entity properties. There is no value processing so caller must make sure
   *   the provided entity property exists for given entity type and its value
   *   is processed.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[]
   *   Array of translatable entities.
   */
  function getTranslatableEntities($entity_type_id, $property_conditions = array()) {

    $target_status = NULL;
    $target_language = NULL;

    // We unset the target_status and target_language, because we can't filter
    // them this way.
    if (isset($property_conditions['target_status']) && isset($property_conditions['target_language'])) {
      $target_status = $property_conditions['target_status'];
      $target_language = $property_conditions['target_language'];
    }
    unset($property_conditions['target_status']);
    unset($property_conditions['target_language']);

    $search = \Drupal::entityQuery($entity_type_id);
    // unset($property_conditions['target_status']);

    foreach ($property_conditions as $property_name => $property_value) {
      $search->condition($property_name, $property_value, 'CONTAINS');
    }

    $result = $search->execute();
    $entities = array();

    if (!empty($result)) {
      // Load the entities.
      $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadMultiple($result);

      // @todo Optimize the code below (code duplication).

      // Remove all entities, that are already translated, because we are looking
      // for untranslated entities.
      if ($target_status == 'untranslated') {
        $entities = array_filter($entities, function (ConfigEntityInterface $entity) use ($target_language) {
          return !$this->isTranslated($target_language, $entity->getConfigDependencyName());
        });
      }
      // Show just the translated entities, and remove the others.
      elseif ($target_status == 'translated') {
        $entities = array_filter($entities, function (ConfigEntityInterface $entity) use ($target_language) {
          return $this->isTranslated($target_language, $entity->getConfigDependencyName());
        });
      }
    }
    return $entities;
  }

  /**
   * Filter translatable definitions.
   *
   * @param array $search_properties
   *   Search properties that are going to be used for the filter.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   Array of translatable definitions.
   */
  public function getFilteredSimpleConfigDefinitions($search_properties = array()) {
    $definitions = \Drupal::service('plugin.manager.config_translation.mapper')->getDefinitions();
    $definitions = array_filter($definitions, function ($definition) use ($search_properties) {
      if (isset($definition['entity_type'])) {
        return FALSE;
      }
      if (isset($search_properties['name']) && strpos(strtolower($definition['title']), strtolower($search_properties['name'])) === FALSE) {
        return FALSE;
      }
      if (isset($search_properties['target_language'])) {
        if ($search_properties['target_status'] == 'translated' && !$this->isTranslated($search_properties['target_language'], $definition['names'][0])) {
          return FALSE;
        }
        if ($search_properties['target_status'] == 'untranslated' && $this->isTranslated($search_properties['target_language'], $definition['names'][0])) {
          return FALSE;
        }
      }
      return TRUE;
    });
    return $definitions;
  }

}
