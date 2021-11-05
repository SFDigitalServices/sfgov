<?php

namespace Drupal\cer\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cer\Entity\CorrespondingReferenceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for corresponding reference add and edit forms.
 */
class CorrespondingReferenceForm extends EntityForm {

  /** @var EntityTypeManagerInterface */
  protected $entityTypeManager;

  /** @var  EntityFieldManager */
  protected $fieldManager;

  /**
   * Constructs a CorrespondingReferenceForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManager $field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var EntityTypeManagerInterface $entity_query */
    $entity_type_manager = $container->get('entity_type.manager');

    /** @var EntityFieldManager $field_manager */
    $field_manager = $container->get('entity_field.manager');

    return new static(
      $entity_type_manager,
      $field_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var CorrespondingReferenceInterface $correspondingReference */
    $correspondingReference = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $correspondingReference->label(),
      '#description' => $this->t("Label for the corresponding reference."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $correspondingReference->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$correspondingReference->isNew(),
    ];

    $form['first_field'] = [
      '#type' => 'select',
      '#title' => $this->t('First field'),
      '#description' => $this->t('Select the first field.'),
      '#options' => $this->getFieldOptions(),
      '#default_value' => $correspondingReference->getFirstField(),
      '#required' => TRUE,
    ];

    $form['second_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Second field'),
      '#description' => $this->t('Select the corresponding field. It may be the same field.'),
      '#options' => $this->getFieldOptions(),
      '#default_value' => $correspondingReference->getSecondField(),
      '#required' => TRUE,
    ];

    $form['bundles'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundles'),
      '#description' => $this->t('Select the bundles which should correspond to one another when they have one of the corresponding fields.'),
      '#options' => $this->getBundleOptions(),
      '#multiple' => TRUE,
      '#default_value' => $this->getBundleValuesForForm($correspondingReference->getBundles()),
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('When enabled, corresponding references will be automatically created upon saving an entity.'),
      '#default_value' => $correspondingReference->isEnabled(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var CorrespondingReferenceInterface $correspondingReference */
    $correspondingReference = $this->entity;

    $status = $correspondingReference->save();

    if ($status) {
      $this->messenger()->addStatus($this->t('Saved the %label corresponding reference.', [
        '%label' => $correspondingReference->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('The %label corresponding reference was not saved.', [
        '%label' => $correspondingReference->label(),
      ]));
    }

    $form_state->setRedirect('entity.corresponding_reference.collection');
  }

  /**
   * Helper function to check whether a corresponding reference configuration entity exists.
   */
  public function exists($id) {
    $entity = $this->entityTypeManager->getStorage('corresponding_reference')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Gets a map of possible reference fields.
   *
   * @return array
   *   The reference field map.
   */
  protected function getReferenceFieldMap() {
    $map = $this->fieldManager->getFieldMapByFieldType('entity_reference');

    return $map;
  }

  /**
   * Gets an array of field options to populate in the form.
   *
   * @return array
   *   An array of field options.
   */
  protected function getFieldOptions() {
    $options = [];

    foreach ($this->getReferenceFieldMap() as $entityType => $entityTypeFields) {
      foreach ($entityTypeFields as $fieldName => $field) {
        if (!preg_match('/^field_.*$/', $fieldName)) {
          continue;
        }

        $options[$fieldName] = $fieldName;
      }
    }

    return $options;
  }

  /**
   * Gets an array of bundle options to populate in the form.
   *
   * @return array
   *   An array of bundle options.
   */
  protected function getBundleOptions() {
    /** @var CorrespondingReferenceInterface $correspondingReference */
    $correspondingReference = $this->entity;

    $correspondingFields = $correspondingReference->getCorrespondingFields();

    $options = [];

    foreach ($this->getReferenceFieldMap() as $entityType => $entityTypeFields) {
      $includeType = FALSE;

      foreach ($entityTypeFields as $fieldName => $field) {
        if (!empty($correspondingFields) && !in_array($fieldName, $correspondingFields)) {
          continue;
        }

        if (!preg_match('/^field_.*$/', $fieldName)) {
          continue;
        }

        $includeType = TRUE;

        foreach ($field['bundles'] as $bundle) {
          $options["$entityType:$bundle"] = "$entityType: $bundle";
        }
      }

      if ($includeType) {
        $options["$entityType:*"] = "$entityType: *";
      }
    }

    ksort($options);

    return $options;
  }

  /**
   * Gets bundle options value in a format for use in the form.
   *
   * @param array|NULL $values
   *   The values to convert.
   *
   * @return array
   *   The converted values.
   */
  protected function getBundleValuesForForm(array $values = NULL) {
    $formValues = [];

    if (!is_null($values)) {
      foreach ($values as $entityType => $bundles) {
        foreach ($bundles as $bundle) {
          $formValues[] = "$entityType:$bundle";
        }
      }
    }

    return $formValues;
  }

  /**
   * Gets bundle options value in a format for use in the config entity.
   *
   * @param array|NULL $values
   *   The values to convert.
   *
   * @return array
   *   The converted values.
   */
  protected function getBundleValuesForEntity(array $values = NULL) {
    $entityValues = [];

    if (!is_null($values)) {
      foreach ($values as $value) {
        list($entityType, $bundle) = explode(':', $value);

        $entityValues[$entityType][] = $bundle;
      }
    }

    return $entityValues;
  }

  /**
   * Copies form values into the config entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The config entity.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $values = array_diff_key($values, $this->entity->getPluginCollections());
    }

    /** @var CorrespondingReferenceInterface $entity */
    $entity->set('id', $values['id']);
    $entity->set('label', $values['label']);
    $entity->set('first_field', $values['first_field']);
    $entity->set('second_field', $values['second_field']);
    $entity->set('bundles', $this->getBundleValuesForEntity($values['bundles']));
    $entity->set('enabled', $values['enabled']);
  }
}
