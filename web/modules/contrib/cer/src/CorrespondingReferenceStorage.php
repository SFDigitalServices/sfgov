<?php

namespace Drupal\cer;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the Corresponding Reference storage.
 */
class CorrespondingReferenceStorage extends ConfigEntityStorage implements CorrespondingReferenceStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadValid(EntityInterface $entity) {
    $query = $this->getQuery()
      ->condition('enabled', 1);

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    return $this->loadMultiple($result);
  }
}
