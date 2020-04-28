<?php

namespace Drupal\tmgmt\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\Job;

/**
 * Provides the views data for the message entity type.
 */
class JobListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->isSubmittable() && $entity->access('submit')) {
      $operations['submit'] = array(
        'url' => $entity->toUrl()->setOption('query', array('destination' => Url::fromRoute('<current>')->getInternalPath())),
        'title' => t('Submit'),
        'weight' => -10,
      );
    }
    else {
      $operations['manage'] = array(
        'url' => $entity->toUrl()->setOption('query', array('destination' => Url::fromRoute('<current>')->getInternalPath())),
        'title' => t('Manage'),
        'weight' => -10,
      );
    }
    if ($entity->isAbortable() && $entity->access('submit')) {
      $operations['abort'] = array(
        'url' => $entity->toUrl('abort-form')->setOption('query', array('destination' => Url::fromRoute('<current>')->getInternalPath())),
        'title' => t('Abort'),
        'weight' => 10,
      );
    }
    return $operations;
  }

}
