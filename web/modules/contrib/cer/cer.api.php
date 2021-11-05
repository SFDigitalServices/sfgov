<?php

/**
 * @file
 * Describes hooks and plugins provided by the Corresponding entities references module.
 */

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Alters differences found by the CER module, before applying them
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   Entity that hosts entity reference fields to be synchronized.
 * @param array $differences
 *   Differences arrary calculated by CorrespondingReference::calculateDifferences()
 * @param string $correspondingField
 *   Name of the corresponding field.
 *
 * @see \Drupal\cer\Entity\CorrespondingReference::calculateDifferences()
 */
function hook_cer_differences_alter(\Drupal\Core\Entity\ContentEntityInterface $entity,
  array &$differences, $correspondingField) {
  // Do not synchronize differences if entity is not published.
  if (!$entity->isPublished()) {
    $differences = ['add' => [], 'remove' => []];
  }
}
