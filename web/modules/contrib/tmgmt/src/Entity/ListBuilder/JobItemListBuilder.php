<?php

namespace Drupal\tmgmt\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides the views data for the message entity type.
 */
class JobItemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->getCountTranslated() > 0 && $entity->access('accept')) {
      $operations['review'] = array(
        'url' => $entity->toUrl(),
        'title' => t('Review'),
      );
    }
    // Do not display view on unprocessed jobs.
    elseif (!$entity->getJob()->isUnprocessed()) {
      $operations['view'] = array(
        'url' => $entity->toUrl(),
        'title' => t('View'),
      );
    }
    // Display abort button on active or needs review job items.
    if ($entity->isActive() || $entity->isNeedsReview()) {
      $operations['abort'] = array(
        'url' => $entity->toUrl('abort-form')->setOption('query', array('destination' => Url::fromRoute('<current>')->getInternalPath())),
        'title' => t('Abort'),
        'weight' => 10,
      );
    }
    return $operations;
  }

}
