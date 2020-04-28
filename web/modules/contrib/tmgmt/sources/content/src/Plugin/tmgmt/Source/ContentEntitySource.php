<?php

namespace Drupal\tmgmt_content\Plugin\tmgmt\Source;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\SourcePluginBase;
use Drupal\tmgmt\SourcePreviewInterface;
use Drupal\tmgmt\ContinuousSourceInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Entity\Job;

/**
 * Content entity source plugin controller.
 *
 * @SourcePlugin(
 *   id = "content",
 *   label = @Translation("Content Entity"),
 *   description = @Translation("Source handler for entities."),
 *   ui = "Drupal\tmgmt_content\ContentEntitySourcePluginUi"
 * )
 */
class ContentEntitySource extends SourcePluginBase implements SourcePreviewInterface, ContinuousSourceInterface {

  /**
   * Returns the entity for the given job item.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job entity
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  protected function getEntity(JobItemInterface $job_item) {
    return \Drupal::entityTypeManager()->getStorage($job_item->getItemType())->load($job_item->getItemId());
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(JobItemInterface $job_item) {
    if ($entity = $this->getEntity($job_item)) {
      return $entity->label() ?: $entity->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(JobItemInterface $job_item) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if ($entity = \Drupal::entityTypeManager()->getStorage($job_item->getItemType())->load($job_item->getItemId())) {
      if ($entity->hasLinkTemplate('canonical')) {
        $anonymous = new AnonymousUserSession();
        $url = $entity->toUrl();
        $anonymous_access = \Drupal::config('tmgmt.settings')->get('anonymous_access');
        if ($url && $anonymous_access && !$entity->access('view', $anonymous)) {
          $url->setOption('query', [
            'key' => \Drupal::service('tmgmt_content.key_access')
              ->getKey($job_item),
          ]);
        }
        return $url;
      }
    }
    return NULL;
  }

  /**
   * Implements TMGMTEntitySourcePluginController::getData().
   *
   * Returns the data from the fields as a structure that can be processed by
   * the Translation Management system.
   */
  public function getData(JobItemInterface $job_item) {
    $entity = $this->getEntity($job_item);
    if (!$entity) {
      throw new TMGMTException(t('Unable to load entity %type with id %id', array('%type' => $job_item->getItemType(), '%id' => $job_item->getItemId())));
    }
    $languages = \Drupal::languageManager()->getLanguages();
    $id = $entity->language()->getId();
    if (!isset($languages[$id])) {
      throw new TMGMTException(t('Entity %entity could not be translated because the language %language is not applicable', array('%entity' => $entity->language()->getId(), '%language' => $entity->language()->getName())));
    }

    if (!$entity->hasTranslation($job_item->getJob()->getSourceLangcode())) {
      throw new TMGMTException(t('The %type entity %id with translation %lang does not exist.', array('%type' => $entity->getEntityTypeId(), '%id' => $entity->id(), '%lang' => $job_item->getJob()->getSourceLangcode())));
    }

    $translation = $entity->getTranslation($job_item->getJob()->getSourceLangcode());
    $data = $this->extractTranslatableData($translation);
    $entity_form_display = entity_get_form_display($job_item->getItemType(), $entity->bundle(), 'default');
    uksort($data, function ($a, $b) use ($entity_form_display) {
      $a_weight = NULL;
      $b_weight = NULL;
      // Get the weights.
      if ($entity_form_display->getComponent($a) && !is_null($entity_form_display->getComponent($a)['weight'])) {
        $a_weight = (int) $entity_form_display->getComponent($a)['weight'];
      }
      if ($entity_form_display->getComponent($b) && !is_null($entity_form_display->getComponent($b)['weight'])) {
        $b_weight = (int) $entity_form_display->getComponent($b)['weight'];
      }

      // If neither field has a weight, sort alphabetically.
      if ($a_weight === NULL && $b_weight === NULL) {
        return ($a > $b) ? 1 : -1;
      }
      // If one of them has no weight, the other comes first.
      elseif ($a_weight === NULL) {
        return 1;
      }
      elseif ($b_weight === NULL) {
        return -1;
      }
      // If both have a weight, sort by weight.
      elseif ($a_weight == $b_weight) {
        return 0;
      }
      else {
        return ($a_weight > $b_weight) ? 1 : -1;
      }
    });
    return $data;
  }

  /**
   * Extracts translatable data from an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to get the translatable data from.
   *
   * @return array $data
   *   Translatable data.
   */
  public function extractTranslatableData(ContentEntityInterface $entity) {
    $field_definitions = $entity->getFieldDefinitions();
    $exclude_field_types = ['language'];
    $exclude_field_names = ['moderation_state'];

    // Exclude field types from translation.
    $translatable_fields = array_filter($field_definitions, function (FieldDefinitionInterface $field_definition) use ($exclude_field_types, $exclude_field_names) {

      // Field is not translatable.
      if (!$field_definition->isTranslatable()) {
        return FALSE;
      }

      // Field type matches field types to exclude.
      if (in_array($field_definition->getType(), $exclude_field_types)) {
        return FALSE;
      }

      // Field name matches field names to exclude.
      if (in_array($field_definition->getName(), $exclude_field_names)) {
        return FALSE;
      }

      // User marked the field to be excluded.
      if ($field_definition instanceof ThirdPartySettingsInterface) {
        $is_excluded = $field_definition->getThirdPartySetting('tmgmt_content', 'excluded', FALSE);
        if ($is_excluded) {
          return FALSE;
        }
      }
      return TRUE;
    });

    \Drupal::moduleHandler()->alter('tmgmt_translatable_fields', $entity, $translatable_fields);

    $data = array();
    foreach ($translatable_fields as $field_name => $field_definition) {
      $field = $entity->get($field_name);
      $data[$field_name] = $this->getFieldProcessor($field_definition->getType())->extractTranslatableData($field);
    }

    $embeddable_fields = static::getEmbeddableFields($entity);
    foreach ($embeddable_fields as $field_name => $field_definition) {
      $field = $entity->get($field_name);

      /* @var \Drupal\Core\Field\FieldItemInterface $field_item */
      foreach ($field as $delta => $field_item) {
        foreach ($field_item->getProperties(TRUE) as $property_key => $property) {
          // If the property is a content entity reference and it's value is
          // defined, than we call this method again to get all the data.
          if ($property instanceof EntityReference && $property->getValue() instanceof ContentEntityInterface) {
            // All the labels are here, to make sure we don't have empty
            // labels in the UI because of no data.
            $data[$field_name]['#label'] = $field_definition->getLabel();
            if (count($field) > 1) {
              // More than one item, add a label for the delta.
              $data[$field_name][$delta]['#label'] = t('Delta #@delta', array('@delta' => $delta));
            }
            // Get the referenced entity.
            $referenced_entity = $property->getValue();
            // Get the source language code.
            $langcode = $entity->language()->getId();
            // If the referenced entity is translatable and has a translation
            // use it instead of the default entity translation.
            if ($referenced_entity->hasTranslation($langcode)) {
              $referenced_entity = $referenced_entity->getTranslation($langcode);
            }
            $data[$field_name][$delta][$property_key] = $this->extractTranslatableData($referenced_entity);
            // Use the ID of the entity to identify it later, do not rely on the
            // UUID as content entities are not required to have one.
            $data[$field_name][$delta][$property_key]['#id'] = $property->getValue()->id();
          }
        }

      }
    }
    return $data;
  }

  /**
   * Returns fields that should be embedded into the data for the given entity.
   *
   * Includes explicitly enabled fields and composite entities that are
   * implicitly included to the translatable data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to get the translatable data from.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[] $embeddable_fields
   *   A list of field definitions that can be embedded.
   */
  public static function getEmbeddableFields(ContentEntityInterface $entity) {
    // Get the configurable embeddable references.
    $field_definitions = $entity->getFieldDefinitions();
    $embeddable_field_names = \Drupal::config('tmgmt_content.settings')->get('embedded_fields');
    $embeddable_fields = array_filter($field_definitions, function (FieldDefinitionInterface $field_definition) use ($embeddable_field_names) {
      return isset($embeddable_field_names[$field_definition->getTargetEntityTypeId()][$field_definition->getName()]);
    });

    // Get always embedded references.
    $content_translation_manager = \Drupal::service('content_translation.manager');
    foreach ($field_definitions as $field_name => $field_definition) {
      $storage_definition = $field_definition->getFieldStorageDefinition();

      $property_definitions = $storage_definition->getPropertyDefinitions();
      foreach ($property_definitions as $property_definition) {
        // Look for entity_reference properties where the storage definition
        // has a target type setting and that is enabled for content
        // translation.
        if (in_array($property_definition->getDataType(), ['entity_reference', 'entity_revision_reference']) && $storage_definition->getSetting('target_type') && $content_translation_manager->isEnabled($storage_definition->getSetting('target_type'))) {
          // Include field if the target entity has the parent type field key
          // set, which is defined by entity_reference_revisions.
          $target_entity_type = \Drupal::entityTypeManager()->getDefinition($storage_definition->getSetting('target_type'));
          if ($target_entity_type->get('entity_revision_parent_type_field')) {
            $embeddable_fields[$field_name] = $field_definition;
          }
        }
      }
    }

    return $embeddable_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function saveTranslation(JobItemInterface $job_item, $target_langcode) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity($job_item);
    if (!$entity) {
      $job_item->addMessage('The entity %id of type %type does not exist, the job can not be completed.', array(
        '%id' => $job_item->getItemId(),
        '%type' => $job_item->getItemType(),
      ), 'error');
      return FALSE;
    }

    $data = $job_item->getData();
    $this->doSaveTranslations($entity, $data, $target_langcode);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemTypes() {
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $types = array();
    $content_translation_manager = \Drupal::service('content_translation.manager');
    foreach ($entity_types as $entity_type_name => $entity_type) {
      // Entity types with this key set are considered composite entities and
      // always embedded in others. Do not expose them as their own item type.
      if ($entity_type->get('entity_revision_parent_type_field')) {
        continue;
      }
      if ($content_translation_manager->isEnabled($entity_type->id())) {
        $types[$entity_type_name] = $entity_type->getLabel();
      }
    }
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemTypeLabel($type) {
    return \Drupal::entityTypeManager()->getDefinition($type)->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getType(JobItemInterface $job_item) {
    if ($entity = $this->getEntity($job_item)) {
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($job_item->getItemType());
      $entity_type = $entity->getEntityType();
      $bundle = $entity->bundle();
      // Display entity type and label if we have one and the bundle isn't
      // the same as the entity type.
      if (isset($bundles[$bundle]) && $bundle != $job_item->getItemType()) {
        return t('@type (@bundle)', array('@type' => $entity_type->getLabel(), '@bundle' => $bundles[$bundle]['label']));
      }
      // Otherwise just display the entity type label.
      return $entity_type->getLabel();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLangCode(JobItemInterface $job_item) {
    $entity = $this->getEntity($job_item);
    return $entity->getUntranslated()->language()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingLangCodes(JobItemInterface $job_item) {
    if ($entity = $this->getEntity($job_item)) {
      return array_keys($entity->getTranslationLanguages());
    }

    return array();
  }

  /**
   * Saves translation data in an entity translation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which the translation should be saved.
   * @param array $data
   *   The translation data for the fields.
   * @param string $target_langcode
   *   The target language.
   *
   * @throws \Exception
   *   Thrown when a field or field offset is missing.
   */
  protected function doSaveTranslations(ContentEntityInterface $entity, array $data, $target_langcode) {
    // If the translation for this language does not exist yet, initialize it.
    if (!$entity->hasTranslation($target_langcode)) {
      $entity->addTranslation($target_langcode, $entity->toArray());
    }

    $translation = $entity->getTranslation($target_langcode);
    $manager = \Drupal::service('content_translation.manager');
    $manager->getTranslationMetadata($translation)->setSource($entity->language()->getId());

    foreach (Element::children($data) as $field_name) {
      $field_data = $data[$field_name];

      if (!$translation->hasField($field_name)) {
        throw new \Exception("Field '$field_name' does not exist on entity " . $translation->getEntityTypeId() . '/' . $translation->id());
      }

      $field = $translation->get($field_name);
      $field_processor = $this->getFieldProcessor($field->getFieldDefinition()->getType());
      $field_processor->setTranslations($field_data, $field);
    }

    $embeddable_fields = static::getEmbeddableFields($entity);
    foreach ($embeddable_fields as $field_name => $field_definition) {

      if (!isset($data[$field_name])) {
        continue;
      }

      $field = $translation->get($field_name);
      foreach (Element::children($data[$field_name]) as $delta) {
        $field_item = $data[$field_name][$delta];
        foreach (Element::children($field_item) as $property) {
          if ($target_entity = $this->findReferencedEntity($field, $field_item, $delta, $property)) {
            // If the field is an embeddable reference and the property is a
            // content entity, process it recursively.
            $this->doSaveTranslations($target_entity, $field_item[$property], $target_langcode);
          }
        }
      }
    }

    $translation->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewUrl(JobItemInterface $job_item) {
    if ($job_item->getJob()->isActive() && !($job_item->isAborted() || $job_item->isAccepted())) {
      return new Url('tmgmt_content.job_item_preview', ['tmgmt_job_item' => $job_item->id()], ['query' => ['key' => \Drupal::service('tmgmt_content.key_access')->getKey($job_item)]]);
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function continuousSettingsForm(array &$form, FormStateInterface $form_state, Job $job) {
    $continuous_settings = $job->getContinuousSettings();
    $element = array();
    $item_types = $this->getItemTypes();
    asort($item_types);
    $entity_type_manager = \Drupal::entityTypeManager();
    foreach ($item_types as $item_type => $item_type_label) {
      $entity_type = $entity_type_manager->getDefinition($item_type);
      $element[$entity_type->id()]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => $item_type_label,
        '#default_value' => isset($continuous_settings[$this->getPluginId()][$entity_type->id()]) ? $continuous_settings[$this->getPluginId()][$entity_type->id()]['enabled'] : FALSE,
      );
      if ($entity_type->hasKey('bundle')) {
        $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($item_type);
        $element[$entity_type->id()]['bundles'] = array(
          '#title' => $this->getBundleLabel($entity_type),
          '#type' => 'details',
          '#open' => TRUE,
          '#states' => array(
            'invisible' => array(
              'input[name="continuous_settings[' . $this->getPluginId() . '][' . $entity_type->id() . '][enabled]"]' => array('checked' => FALSE),
            ),
          ),
        );
        foreach ($bundles as $bundle => $bundle_label) {
          if (\Drupal::service('content_translation.manager')->isEnabled($entity_type->id(), $bundle)) {
            $element[$entity_type->id()]['bundles'][$bundle] = array(
              '#type' => 'checkbox',
              '#title' => $bundle_label['label'],
              '#default_value' => isset($continuous_settings[$this->getPluginId()][$entity_type->id()]['bundles'][$bundle]) ? $continuous_settings[$this->getPluginId()][$entity_type->id()]['bundles'][$bundle] : FALSE,
            );
          }
        }
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateContinuousItem(Job $job, $plugin, $item_type, $item_id) {
    $continuous_settings = $job->getContinuousSettings();
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity = $entity_type_manager->getStorage($item_type)->load($item_id);
    $translation_manager = \Drupal::service('content_translation.manager');
    $translation = $entity->hasTranslation($job->getTargetLangcode()) ? $entity->getTranslation($job->getTargetLangcode()) : NULL;
    $metadata = isset($translation) ? $translation_manager->getTranslationMetadata($translation) : NULL;

    // If a translation exists and is not marked as outdated, no new job items
    // needs to be created.
    if (isset($translation) && !$metadata->isOutdated()) {
      return FALSE;
    }
    else {
      if ($entity && $entity->getEntityType()->hasKey('bundle')) {
        // The entity type has bundles, check both the entity type setting and
        // the bundle.
        if (!empty($continuous_settings[$plugin][$item_type]['bundles'][$entity->bundle()]) && !empty($continuous_settings[$plugin][$item_type]['enabled'])) {
          return TRUE;
        }
      }
      // No bundles, only check entity type setting.
      elseif (!empty($continuous_settings[$plugin][$item_type]['enabled'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returns the bundle label for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return string
   *   The bundle label.
   */
  protected function getBundleLabel(EntityTypeInterface $entity_type) {
    if ($entity_type->getBundleLabel()) {
      return $entity_type->getBundleLabel();
    }
    if ($entity_type->getBundleEntityType()) {
      return \Drupal::entityTypeManager()
        ->getDefinition($entity_type->getBundleEntityType())
        ->getLabel();
    }
    return $this->t('@label type', ['@label' => $entity_type->getLabel()]);
  }

  /**
   * Returns the field processor for a given field type.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return \Drupal\tmgmt_content\FieldProcessorInterface $field_processor
   *   The field processor for this field type.
   */
  protected function getFieldProcessor($field_type) {
    $definition = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field_type);

    return \Drupal::service('class_resolver')->getInstanceFromDefinition($definition['tmgmt_field_processor']);
  }

  /**
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   * @param array $field_item
   * @param $delta
   * @param $property
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   */
  protected function findReferencedEntity(FieldItemListInterface $field, array $field_item, $delta, $property) {

    // If an id is provided, loop over the field item deltas until we find the matching entity.
    if (isset($field_item[$property]['#id'])) {
      foreach ($field as $item) {
        if ($item->$property instanceof ContentEntityInterface && $item->$property->id() == $field_item[$property]['#id']) {
          return $item->$property;
        }
      }

      // @todo Support loading an entity, throw an exception or log a warning?
    }
    // For backwards compatiblity, also support matching based on the delta.
    elseif ($field->offsetExists($delta) && $field->offsetGet($delta)->$property instanceof ContentEntityInterface) {
      return $field->offsetGet($delta)->$property;
    }
  }

}
