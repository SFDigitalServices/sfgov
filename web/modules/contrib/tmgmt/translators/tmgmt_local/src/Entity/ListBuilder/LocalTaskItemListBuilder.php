<?php

namespace Drupal\tmgmt_local\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides the views data for the message entity type.
 */
class LocalTaskItemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\tmgmt_local\Entity\LocalTaskItem $entity */
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('view', \Drupal::currentUser()) && $entity->getTask()->getAssignee() && $entity->getTask()->getAssignee()->id() == \Drupal::currentUser()->id()) {
      if ($entity->isPending()) {
        $operations['translate'] = [
          'url' => $entity->toUrl(),
          'title' => t('Translate'),
          'weight' => 0,
        ];
      }
      else {
        $operations['view'] = [
          'url' => $entity->toUrl(),
          'title' => t('View'),
          'weight' => 0,
        ];
      }
    }
    return $operations;
  }

}
