<?php

namespace Drupal\cer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\cer\CorrespondingReferenceOperations;

/**
 * Defines a corresponding reference entity.
 *
 * @ConfigEntityType(
 *   id = "corresponding_reference",
 *   label = @Translation("Corresponding reference"),
 *   handlers = {
 *     "list_builder" = "Drupal\cer\CorrespondingReferenceListBuilder",
 *     "storage" = "Drupal\cer\CorrespondingReferenceStorage",
 *     "form" = {
 *       "add" = "Drupal\cer\Form\CorrespondingReferenceForm",
 *       "edit" = "Drupal\cer\Form\CorrespondingReferenceForm",
 *       "delete" = "Drupal\cer\Form\CorrespondingReferenceDeleteForm",
 *       "sync" = "Drupal\cer\Form\CorrespondingReferenceSyncForm",
 *     }
 *   },
 *   config_prefix = "corresponding_reference",
 *   admin_permission = "administer cer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "enabled",
 *     "first_field",
 *     "second_field",
 *     "bundles"
 *   },
 *   links = {
 *     "collection" = "/admin/config/content/cer",
 *     "edit-form" = "/admin/config/content/cer/{corresponding_reference}",
 *     "delete-form" = "/admin/config/content/cer/{corresponding_reference}/delete",
 *     "sync-form" = "/admin/config/content/cer/{corresponding_reference}/sync"
 *   }
 * )
 */
class CorrespondingReference extends ConfigEntityBase implements CorrespondingReferenceInterface {

  /**
   * The corresponding reference machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The corresponding reference label.
   *
   * @var string
   */
  public $label;

  /**
   * The first corresponding field ID.
   *
   * @var string
   */
  public $first_field;

  /**
   * The second corresponding field ID.
   *
   * @var string
   */
  public $second_field;

  /**
   * The corresponding bundles keyed by entity type.
   *
   * Example:
   *   [
   *     'node' => ['article', 'page'],
   *     'commerce_product' => ['default']
   *   ]
   *
   * @var array
   */
  public $bundles;

  /**
   * Whether or not this corresponding reference is enabled.
   *
   * @var bool
   */
  public $enabled;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstField() {
    return $this->first_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstField($firstField) {
    $this->first_field = $firstField;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondField() {
    return $this->second_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecondField($secondFIeld) {
    $this->second_field = $secondFIeld;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    return $this->bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundles(array $bundles) {
    $this->bundles = $bundles;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingFields() {
    $first = $this->getFirstField();
    $second = $this->getSecondField();

    $correspondingFields = [];

    if (!empty($first)) {
      $correspondingFields[$first] = $first;
    }

    if (!empty($second)) {
      $correspondingFields[$second] = $second;
    }

    return $correspondingFields;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCorrespondingFields(FieldableEntityInterface $entity) {
    $hasCorrespondingFields = FALSE;

    foreach ($this->getCorrespondingFields() as $field) {
      if ($entity->hasField($field)) {
        $hasCorrespondingFields = TRUE;

        break;
      }
    }

    return $hasCorrespondingFields;
  }

  /**
   * {@inheritdoc}
   */
  public function synchronizeCorrespondingFields(FieldableEntityInterface $entity, $deleted = FALSE) {
    if (!$this->isValid($entity)) {
      return;
    }

    foreach ($this->getCorrespondingFields() as $fieldName) {
      if (!$entity->hasField($fieldName)) {
        continue;
      }

      $differences = $this->calculateDifferences($entity, $fieldName, $deleted);
      $correspondingField = $this->getCorrespondingField($fieldName);

      // Let other modules alter differences.
      \Drupal::moduleHandler()->alter('cer_differences', $entity, $differences, $correspondingField);

      foreach ($differences as $operation => $entities) {
        /** @var FieldableEntityInterface $correspondingEntity */
        foreach ($entities as $correspondingEntity) {
          if ($correspondingEntity) {
            $this->synchronizeCorrespondingField($entity, $correspondingEntity, $correspondingField, $operation);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isValid(FieldableEntityInterface $entity) {
    $bundles = $this->getBundles();
    $entityTypes = array_keys($bundles);
    $entityType = $entity->getEntityTypeId();

    if (!in_array($entityType, $entityTypes)) {
      return FALSE;
    }

    if (!in_array($entity->bundle(), $bundles[$entityType]) && !in_array('*', $bundles[$entityType])) {
      return FALSE;
    }

    if (!$this->hasCorrespondingFields($entity)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingField($fieldName) {
    $fields = $this->getCorrespondingFields();

    if (count($fields) == 1) {
      return $fieldName;
    }

    unset($fields[$fieldName]);

    return array_shift($fields);
  }

  /**
   * {@inheritdoc}
   */
  public function synchronizeCorrespondingField(FieldableEntityInterface $entity, FieldableEntityInterface $correspondingEntity, $correspondingFieldName, $operation = NULL) {
    if (is_null($operation)) {
      $operation = CorrespondingReferenceOperations::ADD;
    }

    if (!$correspondingEntity->hasField($correspondingFieldName)) {
      return;
    }

    $field = $correspondingEntity->get($correspondingFieldName);

    $values = $field->getValue();

    $index = NULL;

    foreach ($values as $idx => $value) {
      if ($value['target_id'] == $entity->id()) {
        if ($operation == CorrespondingReferenceOperations::ADD) {
          return;
        }

        $index = $idx;
      }
    }

    $set = FALSE;

    switch ($operation) {
      case CorrespondingReferenceOperations::REMOVE:
        if (!is_null($index)) {
          unset($values[$index]);
          $set = TRUE;
        }
        break;
      case CorrespondingReferenceOperations::ADD:
        $values[] = ['target_id' => $entity->id()];
        $set = TRUE;
        break;
    }

    if ($set) {
      $field->setValue($values);
      $correspondingEntity->save();
    }
  }

  /**
   * Return added and removed entities from the provided field.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The current entity.
   * @param string $fieldName
   *   The field name to check.
   * @param bool $deleted
   *   Whether the entity is deleted.
   *
   * @return array
   *   The differences keyed by 'added' and 'removed'.
   */
  protected function calculateDifferences(FieldableEntityInterface $entity, $fieldName, $deleted = FALSE) {
    /** @var FieldableEntityInterface $original */
    $original = isset($entity->original) ? $entity->original : NULL;

    $differences = [
      CorrespondingReferenceOperations::ADD => [],
      CorrespondingReferenceOperations::REMOVE => [],
    ];

    if (!$entity->hasField($fieldName)) {
      return $differences;
    }

    $entityField = $entity->get($fieldName);

    // If entity is deleted, remove references to it.
    if ($deleted) {
      /** @var FieldItemInterface $fieldItem */
      foreach ($entityField as $fieldItem) {
        $differences[CorrespondingReferenceOperations::REMOVE][] = $fieldItem->entity;
      }
      return $differences;
    }

    if (empty($original)) {
      foreach ($entityField as $fieldItem) {
        $differences[CorrespondingReferenceOperations::ADD][] = $fieldItem->entity;
      }

      return $differences;
    }

    $originalField = $original->get($fieldName);

    foreach ($entityField as $fieldItem) {
      if (!$this->entityHasValue($original, $fieldName, $fieldItem->target_id)) {
        $differences[CorrespondingReferenceOperations::ADD][] = $fieldItem->entity;
      }
    }

    foreach ($originalField as $fieldItem) {
      if (!$this->entityHasValue($entity, $fieldName, $fieldItem->target_id)) {
        $differences[CorrespondingReferenceOperations::REMOVE][] = $fieldItem->entity;
      }
    }

    return $differences;
  }

  /**
   * Checks if the given entity has the provided corresponding value.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to check.
   * @param string $fieldName
   *   The field name on the entity to check.
   * @param mixed $id
   *   The corresponding ID to check.
   *
   * @return bool
   *   TRUE if value already exists, FALSE otherwise.
   */
  protected function entityHasValue(FieldableEntityInterface $entity, $fieldName, $id) {
    if (!$entity->hasField($fieldName)) {
      return FALSE;
    }

    foreach ($entity->get($fieldName) as $fieldItem) {
      if ($fieldItem->target_id == $id) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
