<?php

namespace Drupal\tmgmt_local\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\tmgmt_local\LocalTaskInterface;

/**
 * Provides the views data for the message entity type.
 */
class LocalTaskListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    /** @var \Drupal\tmgmt_local\Entity\LocalTask $entity */
    if ($entity->access('view')) {
      $operations['view'] = array(
        'title' => $this->t('View'),
        'weight' => -10,
        // Backwards compatibility with Drupal <8.5, which does not yet
        // provide ensureDestination().
        'url' => method_exists($this, 'ensureDestination') ? $this->ensureDestination($entity->toUrl('canonical')) : $entity->toUrl('canonical'),
      );
    }

    if (\Drupal::currentUser()->hasPermission('administer translation tasks') && tmgmt_local_translation_access($entity) && $entity->getStatus() == LocalTaskInterface::STATUS_UNASSIGNED) {
      $operations['assign'] = array(
        'title' => $this->t('Assign'),
        'weight' => 0,
        'url' => $entity->toUrl('assign'),
      );
    }
    elseif (tmgmt_local_translation_access($entity) && $entity->getStatus() == LocalTaskInterface::STATUS_UNASSIGNED) {
      $operations['assign_to_me'] = array(
        'title' => $this->t('Assign to me'),
        'weight' => 0,
        'url' => $entity->toUrl('assign_to_me'),
      );
    }
    if ($entity->getStatus() != LocalTaskInterface::STATUS_UNASSIGNED && $entity->access('unassign')) {
      $operations['unassign'] = array(
        'title' => $this->t('Unassign'),
        'weight' => 0,
        'url' => $entity->toUrl('unassign'),
      );
    }
    return $operations;
  }

}
