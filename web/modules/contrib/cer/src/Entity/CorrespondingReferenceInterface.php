<?php

namespace Drupal\cer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Defines the interface for corresponding reference config entities.
 */
interface CorrespondingReferenceInterface extends ConfigEntityInterface {

  /**
   * Gets the corresponding reference machine name.
   *
   * @return string
   *   The machine name.
   */
  public function getId();

  /**
   * Sets the corresponding reference machine name.
   *
   * @param string $id
   *   The machine name.
   *
   * @return $this
   */
  public function setId($id);

  /**
   * Gets the corresponding reference label.
   *
   * @return string
   *   The label.
   */
  public function getLabel();

  /**
   * Sets the corresponding reference label.
   *
   * @param string $label
   *   The label.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Gets the first corresponding reference field id.
   *
   * @return string
   *   The first corresponding reference field.
   */
  public function getFirstField();

  /**
   * Sets the first corresponding reference field ID.
   *
   * @param string $firstField
   *   The first corresponding reference field ID.
   *
   * @return $this
   */
  public function setFirstField($firstField);

  /**
   * Gets the second corresponding reference field id.
   *
   * @return string
   *   The second corresponding reference field.
   */
  public function getSecondField();

  /**
   * Sets the second corresponding reference field ID.
   *
   * @param string $secondField
   *   The second corresponding reference field ID.
   *
   * @return $this
   */
  public function setSecondField($secondField);

  /**
   * Gets an array of referenced bundle names keyed by entity ID.
   *
   * @return array
   *   The referenced bundles, keyed by entity ID.
   */
  public function getBundles();

  /**
   * Sets the array of referenced bundle names keyed by entity ID.
   *
   * @param array $bundles
   *   The new referenced bundle names, keyed by entity ID.
   *
   * @return $this
   */
  public function setBundles(array $bundles);

  /**
   * Get whether the corresponding reference is enabled.
   *
   * @return bool
   *   TRUE if the corresponding reference is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets whether the corresponding reference is enabled.
   *
   * @param bool $enabled
   *   Whether the corresponding reference is enabled.
   *
   * @return $this
   */
  public function setEnabled($enabled);

  /**
   * Gets an array of the corresponding field names.
   *
   * @return array
   *   The corresponding field names.
   */
  public function getCorrespondingFields();

  /**
   * Gets the name of the corresponding field of the provided field.
   *
   * @param $fieldName string
   *   The provided field name.
   *
   * @return string
   *   The corresponding field name.
   */
  public function getCorrespondingField($fieldName);

  /**
   * Checks if this corresponding reference is valid for the provided entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if this reference field applies to the entity, FALSE otherwise.
   */
  public function isValid(FieldableEntityInterface $entity);

  /**
   * Checks whether the given entity has the configured corresponding reference fields.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity has corresponding fields, FALSE otherwise.
   */
  public function hasCorrespondingFields(FieldableEntityInterface $entity);

  /**
   * Synchronizes corresponding fields on the given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param bool $deleted
   *   Whether the entity is deleted.
   */
  public function synchronizeCorrespondingFields(FieldableEntityInterface $entity, $deleted);

  /**
   * Synchronizes a single corresponding field on a corresponding entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The original entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $correspondingEntity
   *   The corresponding entity.
   *
   * @param $fieldName
   *   The field name.
   */
  public function synchronizeCorrespondingField(FieldableEntityInterface $entity, FieldableEntityInterface $correspondingEntity, $fieldName);
}
