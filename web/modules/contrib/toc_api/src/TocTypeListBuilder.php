<?php
/**
 * @file
 * Contains Drupal\toc_api\TocTypeListBuilder.
 */

namespace Drupal\toc_api;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of TOC type entities.
 *
 * @see \Drupal\toc_api\Entity\TocType
 */
class TocTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['template'] = $this->t('Template');
    $header['headers'] = $this->t('Header(s)');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $options = $entity->getOptions();

    $row['label'] = $entity->label();
    $row['template'] = $options['template'];
    if ($options['header_min'] != $options['header_max']) {
      $row['header'] = '<h' . $options['header_min'] . '> to <h' . $options['header_max'] . '>';
    }
    else {
      $row['header'] = '<h' . $options['header_min'] . '>';
    }
    return $row + parent::buildRow($entity);
  }

}
