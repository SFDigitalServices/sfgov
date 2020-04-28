<?php

/**
 * @file
 * Contains \Drupal\mandrill_activity\Controller\MandrillActivityListBuilder.
 */

namespace Drupal\mandrill_activity\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of MandrillActivity entities.
 *
 * @ingroup mandrill_activity
 */
class MandrillActivityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label() . ' (Machine name: ' . $entity->id() . ')';

    return $row + parent::buildRow($entity);
  }

}
